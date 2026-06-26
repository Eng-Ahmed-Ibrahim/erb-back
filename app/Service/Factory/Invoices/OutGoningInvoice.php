<?php

namespace App\Service\Factory\Invoices;

use App\Models\Invoice;
use App\Models\RecipeQuantity;
use App\Service\Factory\AbstractInvoice;
use App\Service\Factory\InvoiceInterface;
use App\Service\Factory\Invoices\Traits\UsesLedgerProcessor;
use App\Service\Factory\Invoices\Operators\OutGoingInvoiceOperator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutGoningInvoice extends AbstractInvoice implements InvoiceInterface
{
    use UsesLedgerProcessor;

    protected $useLedgerProcessor = true; // Feature flag

    public function __construct()
    {
        parent::__construct();
    }

    protected function getOperator()
    {
        return app(OutGoingInvoiceOperator::class);
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
            \Log::error('OutGoing invoice creation failed', ['error' => $e->getMessage()]);
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

    public function processCreateData($data)
    {
        $department = auth('api')->user()->department;
        $data['from'] = $department->id;
        $data['supplier_id'] = null;
        //  $data['code'] = $this->generateCode($this->invoiceRepositry);
        $data['invoice_date'] = $data['invoice_date'] ?? now();

        foreach ($data['recipes'] as &$recipe) {
            $recipe['price'] = $this->getAvaragePrice($recipe, $department)['price'];
            $recipe['expire_date'] = $this->getExpireDate($department, $recipe);
        }

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

    public function getAvaragePrice($recipe, $department)
    {
        $requestedQuantity = $recipe['quantity'];

        $store = DB::table('department_store')->where('recipe_id', '=', $recipe['recipe_id'])->where('department_id', '=', $department->id)->first();

        $baseQuery = RecipeQuantity::where('department_store_id', $store->id)
            ->where('recipe_id', $recipe['recipe_id'])
            ->orderBy('expire_date', 'asc');

        $recipesQuantitesQuery = clone $baseQuery;

        if (!empty($recipe['invoice_id'])) {
            $recipesQuantitesQuery->where('invoice_id', $recipe['invoice_id']);
        }

        $recipesQuantites = $recipesQuantitesQuery->get();

        if ($recipesQuantites->isEmpty() && !empty($recipe['invoice_id'])) {
            $recipesQuantites = $baseQuery->get();
        }

        $totalPrice = 0;
        $quantityToAccount = $requestedQuantity;

        foreach ($recipesQuantites as $storeQuantity) {
            if ($quantityToAccount <= 0) {
                break;
            }

            $available = $storeQuantity->remaining;
            $used = min($available, $quantityToAccount);
            $totalPrice += $storeQuantity->price * $used;
            $quantityToAccount -= $used;
        }

        $pricedQuantity = $requestedQuantity - $quantityToAccount;
        $avragePrice = $pricedQuantity > 0 ? $totalPrice / $pricedQuantity : 0;

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

        DB::beginTransaction();

        foreach ($recipes as $recipe) {
            DB::beginTransaction();

            $quantity = $recipe->pivot->quantity ?? 0;
            if ($quantity > $fromDepartment->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot->quantity) {
                return [
                    'status' => false,
                    'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من الصنف $recipe->name",
                ];
            }
            $invoiceRecipeId = null;
            foreach ($data['recipes'] as $key => $rowRecipe) {
                if ($rowRecipe['recipe_id'] == $recipe->id) {
                    $invoiceRecipeId = $rowRecipe['invoice_id'];
                    unset($data['recipes'][$key]);
                    break;
                }
            }

            if (!$invoiceRecipeId && $recipe->pivot?->source_invoice_id) {
                $invoiceRecipeId = $recipe->pivot->source_invoice_id;
            }

            $this->UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $invoiceRecipeId, $quantity);
            $this->createOrUpdatePivot($fromDepartment, $recipe);
            $this->createOrUpdatePivot($toDepartment, $recipe);
            DB::commit();
        }
        DB::commit();

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

        $baseQuery = RecipeQuantity::where('department_store_id', $fromPivotId)
            ->where('remaining', '>', 0)
            ->orderBy('expire_date', 'asc');

        $quantitesQuery = clone $baseQuery;

        if (!empty($invoiceRecipeId)) {
            $quantitesQuery->where('invoice_id', $invoiceRecipeId);
        }

        $quantites = $quantitesQuery->get();

        if ($quantites->isEmpty() && !empty($invoiceRecipeId)) {
            Log::warning('Falling back to FIFO for recipe quantity lookup', [
                'department_store_id' => $fromPivotId,
                'recipe_id' => $recipe->id,
                'invoice_id' => $invoiceRecipeId,
            ]);
            $quantites = $baseQuery->get();
        }

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

        $baseQuery = RecipeQuantity::where('department_store_id', $id)
            ->where('recipe_id', $recipe['recipe_id'])
            ->orderBy('expire_date', 'asc')
            ->limit(1);

        if (!empty($recipe['invoice_id'])) {
            $baseQuery->where('invoice_id', $recipe['invoice_id']);
        }

        $recipesQuantites = $baseQuery->first();

        if (!$recipesQuantites && !empty($recipe['invoice_id'])) {
            $recipesQuantites = RecipeQuantity::where('department_store_id', $id)
                ->where('recipe_id', $recipe['recipe_id'])
                ->orderBy('expire_date', 'asc')
                ->first();
        }

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
