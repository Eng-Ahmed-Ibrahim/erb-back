<?php

namespace App\Service\Factory\Invoices;

use App\Models\Invoice;
use App\Models\RecipeQuantity;
use App\Service\Factory\AbstractInvoice;
use App\Service\Factory\InvoiceInterface;
use App\Service\Factory\Invoices\Traits\UsesLedgerProcessor;
use App\Service\Factory\Invoices\Operators\TransfareInvoiceOperator;
use Illuminate\Support\Facades\DB;

class TransfareInvoice extends AbstractInvoice implements InvoiceInterface
{
    use UsesLedgerProcessor;

    protected $useLedgerProcessor = true; // Feature flag

    public function __construct()
    {
        parent::__construct();
    }

    protected function getOperator()
    {
        return app(TransfareInvoiceOperator::class);
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
                $createService = $this->takingOutInvoice($invoice, $data);
                if ($createService['status'] == false) {
                    DB::rollBack();
                    return $createService;
                }
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfare invoice creation failed', ['error' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateInvoicePrices($invoice, $data)
    {
        return [
            'status' => false,
            'message' => 'لا يمكن تعديل اسعار اذون الصرف',
        ];
    }

    public function updateInvoiceQuantity($invoice, $data)
    {
        $fromDepartment = $invoice->fromDepartment;
        $toDepartment = $invoice->toDepartment;
        $oldRecipes = $invoice->recipes;

        DB::beginTransaction();
        foreach ($data['recipes'] as $newRecipe) {
            foreach ($oldRecipes as $oldRecipe) {
                if ($newRecipe['recipe_id'] == $oldRecipe['id']) {
                    if ($newRecipe['quantity'] > $fromDepartment->recipes()->wherePivot('recipe_id', $oldRecipe->id)->first()?->pivot->quantity && ($newRecipe['quantity'] > $oldRecipe->pivot->quantity)) {
                        return [
                            'status' => false,
                            'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من الصنف $oldRecipe->name",
                        ];
                    }

                    $checkpoint = $oldRecipe->pivot->quantity - $newRecipe['quantity'];
                    $this->updateInvoiceRecipes($invoice, $newRecipe);
                    $newRecipe['quantity'] = $checkpoint;
                    $storeFromId = $this->getPivotId($fromDepartment, $oldRecipe);
                    $storeToId = $this->getPivotId($toDepartment, $oldRecipe);

                    $this->updateStoreQuantity($storeFromId, $newRecipe, true);
                    $this->createOrUpdatePivot($fromDepartment, $oldRecipe);

                    $this->updateStoreQuantity($storeToId, $newRecipe, false);
                    $this->createOrUpdatePivot($toDepartment, $oldRecipe);
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

    public function processCreateData($data)
    {
        $department = auth('api')->user()->department;
        $data['from'] = $data['from'];
        $data['to'] = $data['to'];
        $data['code'] = isset($data['code']) ? $data['code'] : $this->generateCode($this->invoiceRepositry);

        $data['invoice_date'] = $data['invoice_date'] ?? now();

        // foreach ($data['recipes'] as &$recipe) {
        //     $recipe['price'] = $this->getAvaragePrice($recipe, $department)['price'];
        //     $recipe['expire_date'] = $this->getExpireDate($department, $recipe);
        // }

        $data['status'] = 'pending';
        $data['invoice_price'] = $this->calculateTotalPrice($data['recipes']);
        $data['total_price'] = $data['invoice_price'];

        // if (isset($data['discount']) && $data['discount']) {
        //     $data['total_price'] = $data['invoice_price'] - $data['discount'];
        // }
        // if (isset($data['tax']) && $data['tax']) {
        //     $data['total_price'] = $data['total_price'] + $data['tax'];
        // }
        $this->data = $data;
    }

    public function getAvaragePrice($recipe, $department)
    {
        $quantity = $recipe['quantity'];

        $store = DB::table('department_store')->where('recipe_id', '=', $recipe['recipe_id'])->where('department_id', '=', $department->id)->first();

        $recipesQuantites = RecipeQuantity::where('department_store_id', $store->id)
            ->where('recipe_id', $recipe['recipe_id'])
            ->when(isset($recipe['invoice_id']) && $recipe['invoice_id'] != '', fn ($query) => $query
                ->where('invoice_id', $recipe['invoice_id']))
            ->orderBy('expire_date', 'asc')
            ->get();

        $totalPrice = 0;
        foreach ($recipesQuantites as $store) {
            if ($quantity > $store->remaining) {
                $totalPrice += $store->price * $store->remaining;
                $quantity -= $store->remaining;
            } else {
                $totalPrice += $store->price * $quantity;
                break;
            }
        }
        $avragePrice = $quantity > 0 ? $totalPrice / $quantity : 0;

        return [
            'status' => true,
            'price' => $avragePrice,
        ];
    }

    public function updateInvoiceRecipes($invoice, $newRecipe)
    {
        $invoiceRecipe = $invoice->recipes()->wherePivot('recipe_id', $newRecipe['recipe_id'])->first()->pivot;
        $totalPrice = $invoiceRecipe->price * $newRecipe['quantity'];
        $invoiceRecipe->update([
            'quantity' => $newRecipe['quantity'],
            'total_price' => $totalPrice,
        ]);
    }

    public function takingOutInvoice($invoice, $data)
    {
        $fromDepartment = $invoice->fromDepartment;
        $toDepartment = $invoice->toDepartment;

        $recipes = $invoice->recipes;

        foreach ($recipes as $recipe) {
            $quantity = $recipe->pivot->quantity ?? 0;
            if ($quantity > $fromDepartment->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot->quantity) {
                return [
                    'status' => false,
                    'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من الصنف $recipe->name",
                ];
            }
            $invoiceRecipeId = '';
            foreach ($data['recipes'] as $rowRecipe) {
                if ($rowRecipe['recipe_id'] == $recipe->id) {
                    $invoiceRecipeId = $rowRecipe['invoice_id'];
                }
            }

            $this->UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $invoiceRecipeId, $quantity);
            $this->createOrUpdatePivot($fromDepartment, $recipe);
            $this->createOrUpdatePivot($toDepartment, $recipe);
        }

        return [
            'status' => true,
            'message' => 'تم تسجيل اذن صرف لقسم بنجاح',
        ];
    }

    public function UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $invoiceRecipeId, $quantity, $isReturned = false)
    {
        $fromPivotId = $this->getPivotId($fromDepartment, $recipe);
        if (! $isReturned) {
            $this->createPivotIfNotExist($toDepartment, $recipe);
        }

        $quantites = RecipeQuantity::where('department_store_id', $fromPivotId)
            ->when(isset($invoiceRecipeId) && $invoiceRecipeId != '', fn ($query) => $query
                ->where('invoice_id', $invoiceRecipeId))
            ->where('remaining', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->get();

        foreach ($quantites as $recipeQuantity) {
            $remaining = $recipeQuantity->remaining;
            $invoiceId = $recipeQuantity->invoice_id;
            if ($remaining >= $quantity) {
                $recipeQuantity->remaining = $remaining - $quantity;
                $recipeQuantity->total_price = $recipeQuantity->remaining * $recipeQuantity->price;
                $recipeQuantity->save();
                if (! $isReturned) {
                    $this->createOrUpdateRecipeQuantity($toDepartment, $recipe, $invoiceId);
                }
                if ($recipeQuantity->remaining == 0) {
                    $recipeQuantity->delete();
                }
                break;
            } else {
                $recipeQuantity->remaining = 0;
                $recipeQuantity->total_price = $recipeQuantity->remaining * $recipeQuantity->price;
                $recipeQuantity->save();
                $quantity -= $remaining;
                $recipeQuantity->delete();
            }
        }
    }

    public function getExpireDate($department, $recipe)
    {
        $id = DB::table('department_store')
            ->select('id')
            ->where('recipe_id', '=', $recipe['recipe_id'])
            ->where('department_id', '=', $department->id)
            ->first()
            ->id;

        $recipesQuantites = RecipeQuantity::where('department_store_id', $id)
            ->where('recipe_id', $recipe['recipe_id'])
            ->when(isset($recipe['invoice_id']) && $recipe['invoice_id'] != '', fn ($query) => $query
                ->where('invoice_id', $recipe['invoice_id']))
            ->orderBy('expire_date', 'asc')
            ->first();

        return $recipesQuantites?->expire_date;
    }

    protected function getPivotId($department, $recipe)
    {
        return DB::table('department_store')
            ->select('id')
            ->where('recipe_id', '=', $recipe->id)
            ->where('department_id', '=', $department->id)
            ->first()
            ->id;
    }
}
