<?php

namespace App\Service\Factory\Invoices;

use App\Service\Factory\AbstractInvoice;
use App\Service\Factory\InvoiceInterface;
use App\Service\Factory\Invoices\Traits\UsesLedgerProcessor;
use App\Service\Factory\Invoices\Operators\ReturnedInvoiceOperator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryArchive;
use App\Models\RecipeQuantity;

class ReturnedInvoice extends AbstractInvoice implements InvoiceInterface
{
    use UsesLedgerProcessor;

    protected $useLedgerProcessor = true; // Feature flag

    public function __construct()
    {
        parent::__construct();
    }

    protected function getOperator()
    {
        return app(ReturnedInvoiceOperator::class);
    }

    public function createInvoice($data)
    {
        $this->processCreateData($data);
        $data['to'] = auth('api')->user()->department->id;

        DB::beginTransaction();
        try {
            $invoice = $this->invoiceRepositry->adminCreate($this->data);

            if (config('app.isInventoryLedgerEnabled', false)) {
                $result = $this->processViaLedgerProcessor($invoice);
                if (!$result['status']) {
                    DB::rollBack();
                    return $result;
                }
            } else {
                // LEGACY: Use old method
                $createService = $this->returnedInvoice($invoice);
                if (isset($createService['status']) && $createService['status'] == false) {
                    DB::rollBack();
                    return $createService;
                }
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Returned invoice creation failed', ['error' => $e->getMessage()]);
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
            'message' => 'لا يمكن تعديل اسعار اذون الإرجاع',
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
                    if ($newRecipe['quantity'] > $fromDepartment->recipes()->wherePivot('recipe_id', $oldRecipe->id)->first()?->pivot->quantity) {
                        return [
                            'status' => false,
                            'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من الصنف $oldRecipe->name",
                        ];
                    }
                    $invoiceRecipePivot = $invoice->recipes()->wherePivot('recipe_id', $newRecipe['recipe_id'])->first()->pivot;
                    $sourceInvoiceId = $invoiceRecipePivot->source_invoice_id ?? null;

                    $checkpoint = $invoiceRecipePivot->quantity - $newRecipe['quantity'];
                    $this->updateInvoiceRecipes($invoice, $newRecipe);

                    $adjustmentRecipe = $newRecipe;
                    $adjustmentRecipe['quantity'] = $checkpoint;
                    $adjustmentRecipe['price'] = $invoiceRecipePivot->price;
                    $adjustmentRecipe['expire_date'] = $invoiceRecipePivot->expire_date;

                    $storeFromId = $this->getPivotId($fromDepartment, $oldRecipe);
                    $storeToId = $this->getPivotId($toDepartment, $oldRecipe);

                    if ($checkpoint !== 0) {
                        $this->updateStoreQuantity($storeFromId, $adjustmentRecipe, true, $sourceInvoiceId);
                        $this->createOrUpdatePivot($fromDepartment, $oldRecipe);
                        $this->updateStoreQuantity($storeToId, $adjustmentRecipe, false, $sourceInvoiceId);
                        $this->createOrUpdatePivot($toDepartment, $oldRecipe);
                    } else {
                        $this->createOrUpdatePivot($fromDepartment, $oldRecipe);
                        $this->createOrUpdatePivot($toDepartment, $oldRecipe);
                    }

                    continue 2;



                    // // SENCE THE DIFF
                    // $diffrence = $newRecipe['quantity'] - $oldRecipe['quantity'];
                    // $diffrence = (float) $diffrence;

                    // // FETCH ALL RECORDS TO BE UPDATES, THEN UPDATE
                    // InventoryArchive::where('recipe_id', '=', $newRecipe['recipe_id'])
                    //     ->where('department_id', '=', $invoice->to)
                    //     ->where('captured_at', '>=', $invoice->created_at)
                    //     ->where('captured_at', '<=', carbon::now())
                    //     ->update([
                    //         'quantity' => \DB::raw('quantity + '.$diffrence),
                    //         'updated_at' => carbon::now(),
                    //     ]);


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

    public function updateStoreQuantity($idStore, $newRecipe, $add, $invoiceId = null)
    {
        $query = RecipeQuantity::where('department_store_id', $idStore)
            ->where('recipe_id', $newRecipe['recipe_id'])
            ->where('expire_date', $newRecipe['expire_date']);

        if (!empty($invoiceId)) {
            $query->where('invoice_id', $invoiceId);
        }

        $recipeQuantity = $query->first();

        if (!$recipeQuantity && !empty($invoiceId)) {
            Log::warning('Returned invoice store quantity adjustment without matching invoice id', [
                'department_store_id' => $idStore,
                'recipe_id' => $newRecipe['recipe_id'],
                'source_invoice_id' => $invoiceId,
            ]);

            $recipeQuantity = RecipeQuantity::where('department_store_id', $idStore)
                ->where('recipe_id', $newRecipe['recipe_id'])
                ->where('expire_date', $newRecipe['expire_date'])
                ->first();
        }

        if (!$recipeQuantity) {
            throw new \Exception('لا يمكن تعديل الكمية لمكون غير موجود في المخزون (فاتورة الإرجاع)');
        }

        if ($add) {
            $remaining = $recipeQuantity->remaining + $newRecipe['quantity'];
            $totalPrice = $recipeQuantity->price * $remaining;
        } else {
            $remaining = $recipeQuantity->remaining - $newRecipe['quantity'];
            $totalPrice = $recipeQuantity->price * $remaining;
        }
        $recipeQuantity->update([
            'remaining' => $remaining,
            'total_price' => $totalPrice,
        ]);
    }

    public function returnedInvoice($invoice)
    {
        $returnedInvoice = $this->takingOutInvoice($invoice);
        if ($returnedInvoice['status'] == false) {
            return $returnedInvoice;
        }

        return [
            'status' => true,
            'message' => 'تم تسجيل فاتورة الإرجاع بنجاح',
        ];
    }

    public function processCreateData($data)
    {
        $departmentTo = auth('api')->user()->department;
        $data['to'] = $departmentTo->id;
        $data['code'] = $this->generateCode($this->invoiceRepositry);
        $data['invoice_date'] = now();
        $data['status'] = 'pending';
        $data['invoice_price'] = $this->calculateTotalPrice($data['recipes']);
        $data['total_price'] = $data['invoice_price'];
        if (isset($data['discount']) && $data['discount']) {
            $data['total_price'] = $data['invoice_price'] - $data['discount'];
        }
        if (isset($data['tax']) && $data['tax']) {
            $data['total_price'] = $data['total_price'] + $data['tax'];
        }
        $this->data = $data;
    }

    public function getPrice($recipe, $department)
    {
        $storeId = DB::table('department_store')->where('recipe_id', '=', $recipe['recipe_id'])->where('department_id', '=', $department->id)->first()->id;
        $query = RecipeQuantity::where('department_store_id', $storeId)
            ->where('recipe_id', $recipe['recipe_id'])
            ->where('price', $recipe['price'])
            ->where('expire_date', $recipe['expire_date']);

        if (!empty($recipe['invoice_id'])) {
            $query->where('invoice_id', $recipe['invoice_id']);
        }

        $recipesQuantites = $query->first();

        if (!$recipesQuantites && !empty($recipe['invoice_id'])) {
            $recipesQuantites = RecipeQuantity::where('department_store_id', $storeId)
                ->where('recipe_id', $recipe['recipe_id'])
                ->where('price', $recipe['price'])
                ->where('expire_date', $recipe['expire_date'])
                ->first();
        }

        return $recipesQuantites->price ?? 0;
    }

    public function takingOutInvoice($invoice)
    {
        $fromDepartment = $invoice->fromDepartment;
        $toDepartment = $invoice->toDepartment;
        $recipes = $invoice->recipes()->get();
        foreach ($recipes as $recipe) {
            $quantity = $recipe->pivot->quantity ?? 0;

            if ($quantity > $fromDepartment->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot->quantity ?? 0) {
                return [
                    'status' => false,
                    'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من  $recipe->name",
                ];
            }
            $sourceInvoiceId = $recipe->pivot->source_invoice_id ?? null;

            $this->UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $quantity, $sourceInvoiceId);
            $this->createOrUpdatePivot($fromDepartment, $recipe);
            $this->createOrUpdatePivot($toDepartment, $recipe);
        }

        return [
            'status' => true,
            'message' => 'تم تسجيل اذن صرف لقسم بنجاح',
        ];
    }

    protected function UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $incomeQuantity, $sourceInvoiceId = null)
    {
        $fromPivotId = $this->getPivotId($fromDepartment, $recipe);

        $quantityQuery = RecipeQuantity::where('department_store_id', $fromPivotId)
            ->where('recipe_id', $recipe->id)
            ->where('price', $recipe->pivot->price)
            ->where('expire_date', $recipe->pivot->expire_date);

        if (!empty($sourceInvoiceId)) {
            $quantityQuery->where('invoice_id', $sourceInvoiceId);
        }

        $quantity = $quantityQuery->first();

        if (!$quantity && !empty($sourceInvoiceId)) {
            Log::warning('Returned invoice quantity lookup fell back without invoice id', [
                'department_store_id' => $fromPivotId,
                'recipe_id' => $recipe->id,
                'source_invoice_id' => $sourceInvoiceId,
            ]);

            $quantity = RecipeQuantity::where('department_store_id', $fromPivotId)
                ->where('recipe_id', $recipe->id)
                ->where('price', $recipe->pivot->price)
                ->where('expire_date', $recipe->pivot->expire_date)
                ->first();
        }

        $remaining = $quantity?->remaining ?? 0;
        $invoiceId = $quantity?->invoice_id ?? $sourceInvoiceId;

        if ($remaining >= $incomeQuantity) {
            $quantity->remaining = $remaining - $incomeQuantity;
            $quantity->total_price = $quantity->remaining * $quantity->price;
            $quantity->save();
            $this->createOrUpdateRecipeQuantities($toDepartment, $recipe, $invoiceId);
            if ($quantity->remaining == 0) {
                $quantity->delete();
            }
        }
    }

    protected function createOrUpdateRecipeQuantities($department, $recipe, $invoice_id)
    {
        $recipeQuantity = $this->recipeQuantitiesRepository->findByAttributes([
            'department_store_id' => $this->getPivotId($department, $recipe),
            'recipe_id' => $recipe->id,
            'expire_date' => $recipe->pivot->expire_date,
            'invoice_id' => $invoice_id,
        ]);
        if ($recipeQuantity) {
            $recipeQuantity->remaining = $recipeQuantity->remaining + $recipe->pivot->quantity;
            $recipeQuantity->total_price = $recipeQuantity->remaining * $recipe->pivot->price;
            $recipeQuantity->save();
        } else {
            $this->recipeQuantitiesRepository->create([
                'department_store_id' => $this->getPivotId($department, $recipe),
                'recipe_id' => $recipe->id,
                'expire_date' => $recipe->pivot->expire_date,
                'price' => $recipe->pivot->price,
                'remaining' => $recipe->pivot->quantity,
                'total_price' => $recipe->pivot->price * $recipe->pivot->quantity,
                'invoice_id' => $invoice_id]);
        }
    }
}
