<?php

namespace App\Service\Factory\Invoices;

use App\Models\Department;
use App\Service\Factory\AbstractInvoice;
use App\Service\Factory\InvoiceInterface;
use App\Service\Factory\Invoices\Traits\UsesLedgerProcessor;
use App\Service\Factory\Invoices\Operators\TaintedInvoiceOperator;
use Illuminate\Support\Facades\DB;

class TaintedInvoice extends AbstractInvoice implements InvoiceInterface
{
    use UsesLedgerProcessor;

    protected $useLedgerProcessor = true; // Feature flag

    public function __construct()
    {
        parent::__construct();
    }

    protected function getOperator()
    {
        return app(TaintedInvoiceOperator::class);
    }

    public function createInvoice($data)
    {
        $this->processCreateData($data);

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceRepositry->adminCreate($this->data);

            if (config('app.isInventoryLedgerEnabled', false)) {
                // NEW: Use ledger processor
                $result = $this->processViaLedgerProcessor($invoice);
                if (!$result['status']) {
                    DB::rollBack();
                    return $result;
                }
            } else {
                // LEGACY: Use old method
                $createService = $this->taintedInvoice($invoice);
                if (isset($createService['status']) && $createService['status'] == false) {
                    DB::rollBack();
                    return $createService;
                }
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Tainted invoice creation failed', ['error' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateInvoiceQuantity($invoice, $data)
    {
        $fromDepartment = $invoice->fromDepartment;
        $oldRecipes = $invoice->recipes;
        DB::beginTransaction();
        foreach ($data['recipes'] as $newRecipe) {
            foreach ($oldRecipes as $oldRecipe) {
                if ($newRecipe['recipe_id'] == $oldRecipe['id']) {
                    $checkpoint = $oldRecipe->pivot->quantity - $newRecipe['quantity'];
                    $this->updateInvoiceRecipes($invoice, $newRecipe);
                    $newRecipe['quantity'] = $checkpoint;
                    $storeFromId = $this->getPivotId($fromDepartment, $oldRecipe);
                    $this->updateStoreQuantity($storeFromId, $newRecipe, true);
                    $this->createOrUpdatePivot($fromDepartment, $oldRecipe);
                }
            }
        }
        DB::commit();

        $this->updateInvoice($invoice);

        return [
            'status' => true,
            'message' => 'تم تعديل الفاتورة بنجاح',
        ];
    }

    public function updateInvoicePrices($invoice, $data)
    {
        return [
            'status' => false,
            'message' => 'لا يمكن تعديل اسعار الفواتير الهالكة',
        ];
    }

    public function updateStoreQuantity($idStore, $newRecipe, $add, $invoiceId = null)
    {

        $recipeQuantity = $this->recipeQuantitiesRepository->findByAttributes(['department_store_id' => $idStore,
            'recipe_id' => $newRecipe['recipe_id'],
            'expire_date' => $newRecipe['expire_date']]
        );

        if ($add) {
            $remaining = $recipeQuantity->remaining + $newRecipe['quantity'];
        } else {
            $remaining = $recipeQuantity->remaining - $newRecipe['quantity'];
        }
        $totalPrice = $recipeQuantity->price * $remaining;
        $recipeQuantity->update([
            'remaining' => $remaining,
            'total_price' => $totalPrice,
        ]);
    }

    public function taintedInvoice($invoice)
    {
        $fromDepartment = $invoice->fromDepartment;
        $recipes = $invoice->recipes;
        foreach ($recipes as $recipe) {
            $quantity = $recipe->pivot->quantity ?? 0;
            if ($quantity > $fromDepartment->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot->quantity) {
                return [
                    'status' => false,
                    'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من الصنف $recipe->name",
                ];
            }
            $this->UpdateRecipeQuantites($fromDepartment, $recipe, $quantity);
            $this->createOrUpdatePivot($fromDepartment, $recipe);
        }

        return [
            'status' => true,
            'message' => 'تم تسجيل اذن صرف لقسم بنجاح',
        ];
    }

    public function processCreateData($data)
    {
        $departmentFrom = auth()->user()->department;
        if ($departmentFrom->type == 'master') {
            $departmentFrom = Department::find($data['from']);
        }
        $data['code'] = $this->generateCode($this->invoiceRepositry);
        $data['invoice_date'] = now();
        $data['status'] = 'pending';

        $data['invoice_price'] = $this->calculateTotalPrice($data['recipes']);
        $data['total_price'] = $data['invoice_price'];

        $data['type'] = 'tainted';

        $this->data = $data;
    }

    public function getPrice($recipe, $department)
    {
        $store = $department->recipes()->wherePivot('recipe_id', $recipe['recipe_id'])->first()?->pivot;
        $recipesQuantites = $this->recipeQuantitiesRepository->findByAttributes([
            'department_store_id' => $store->id,
            'recipe_id' => $recipe['recipe_id'],
            'expire_date' => $recipe['expire_date'],
            'price' => $recipe['price'],
        ]);

        return $recipesQuantites->price;
    }

    protected function UpdateRecipeQuantites($fromDepartment, $recipe, $taintedQuantity)
    {

        $fromPivotId = $this->getPivotId($fromDepartment, $recipe);
        $this->createPivotIfNotExist($fromDepartment, $recipe);
        $quantity = $this->recipeQuantitiesRepository->findByAttributes([
            'department_store_id' => $fromPivotId,
            'recipe_id' => $recipe->id,
            'expire_date' => $recipe->pivot->expire_date,
            'price' => $recipe->pivot->price,
        ]);
        $remaining = $quantity->remaining;
        $invoiceId = $quantity->invoice_id;
        if ($remaining >= $taintedQuantity) {
            $quantity->remaining = $remaining - $taintedQuantity;
            $quantity->total_price = $quantity->remaining * $quantity->price;
            $quantity->save();
            if ($quantity->remaining == 0) {
                $quantity->delete();
            }
        }
    }

    protected function createOrUpdateRecipeQuantity($department, $recipe, $invoice_id)
    {
        $recipeQuantity = $this->recipeQuantitiesRepository->findByAttributes([
            'department_store_id' => $this->getPivotId($department, $recipe),
            'recipe_id' => $recipe->id,
            'expire_date' => $recipe->pivot->expire_date,
        ]);
        if ($recipeQuantity) {
            $recipeQuantity->remaining = $recipeQuantity->remaining + $recipe->pivot->quantity;
            $recipeQuantity->total_price = $recipeQuantity->remaining * $recipe->pivot->price;
            $recipeQuantity->save();
        } else {
            $this->createOrUpdateRecipeQuantity($department, $recipe, $invoice_id);
        }
    }
}
