<?php

namespace App\Service\Factory;

use App\Models\DepartmentStore;
use App\Models\RecipeQuantity;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\RecipeQuantity\RecipeQuantityRepository;
use App\Service\InventoryLedgerRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbstractInvoice
{
    public $invoiceRepositry;

    protected $recipeQuantitiesRepository;

    protected $ledgerRecorder;

    public $data;

    public function __construct()
    {
        $this->invoiceRepositry = app(InvoiceRepository::class);
        $this->recipeQuantitiesRepository = app(RecipeQuantityRepository::class);
        $this->ledgerRecorder = app(InventoryLedgerRecorder::class);
    }

    public function createOrUpdatePivot($department, $recipe)
    {
        $this->createPivotIfNotExist($department, $recipe);

        $pivotId = $this->getPivotId($department, $recipe);

        $quantities = $this->recipeQuantitiesRepository->getByAttributes(['department_store_id' => $pivotId])->where('remaining', '>', 0);

        $Price = $quantities->sum('total_price');
        $remaining = $quantities->sum('remaining');

        $department->recipes()->updateExistingPivot($recipe->id, [
            'quantity' => $remaining,
            'price' => $Price,
        ]);
    }

    protected function createPivotIfNotExist($department, $recipe)
    {
        $pivot = $department->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot;
        if (!$pivot) {
            $department->recipes()->attach($recipe->id, [
                'quantity' => $recipe->pivot->quantity,
                'price' => $recipe->pivot->price * $recipe->pivot->quantity,
            ]);

            return $department->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot;
        }

        return $pivot;
    }

    protected function getPivotId($department, $recipe)
    {
        // return DB::table('department_store')->select('id')->where('recipe_id', '=', $recipe->id)->where('department_id', '=', $department->id)->first()->id;
        $pivot = DB::table('department_store')
            ->select('id')
            ->where('recipe_id', '=', $recipe->id)
            ->where('department_id', '=', $department->id)
            ->first();

        if (!$pivot) {
            $pivot = DepartmentStore::create([
                'recipe_id' => $recipe->id,
                'department_id' => $department->id,
                'price' => 0,
                'quantity' => 0,
            ]);
        }
        return $pivot->id;
    }

    protected function createOrUpdateRecipeQuantity($department, $recipe, $invoice_id)
    {
        $pivotId = $this->getPivotId($department, $recipe);

        $existQuantity = $this->recipeQuantitiesRepository->findByAttributes([
            'department_store_id' => $pivotId,
            'recipe_id' => $recipe->id,
            'invoice_id' => $invoice_id,
            'expire_date' => $recipe->pivot->expire_date,
            'price' => $recipe->pivot->price,
        ]);

        if ($existQuantity) {
            $existQuantity->update([
                'quantity' => $existQuantity->quantity + $recipe->pivot->quantity,
                'total_price' => $existQuantity->total_price + $recipe->pivot->price * $recipe->pivot->quantity,
                'remaining' => $existQuantity->remaining + $recipe->pivot->quantity,
            ]);

            return true;
        }

        return $this->recipeQuantitiesRepository->create([
            'recipe_id' => $recipe->id,
            'expire_date' => $recipe->pivot->expire_date,
            'price' => $recipe->pivot->price,
            'quantity' => $recipe->pivot->quantity,
            'remaining' => $recipe->pivot->quantity,
            'invoice_id' => $invoice_id,
            'total_price' => $recipe->pivot->price * $recipe->pivot->quantity,
            'department_store_id' => $pivotId,
        ]);
    }

    public function generateCode($model)
    {
        do {
            $code = 'INV-' . rand(100000000, 999999999);
        } while ($model->where('code', $code)->exists());

        return $code;
    }

    protected function calculateTotalPrice($recipes)
    {
        $total = 0;
        foreach ($recipes as $recipe) {
            $total += $recipe['quantity'] * $recipe['price'];
        }

        return $total;
    }

    public function processUpdateData($invoice, $data)
    {
        $data['invoice_price'] = $this->calculatePriceInUpdate($invoice, $data['recipes']);

        $data['total_price'] = $data['invoice_price'];
        if ($invoice->discount) {
            if (isset($data['discount']) && $data['discount'] != $invoice->discount) {
                $data['total_price'] = $data['invoice_price'] - $data['discount'];
            } else {
                $data['total_price'] = $data['invoice_price'] - $invoice->discount;
            }
        }
        if ($invoice->tax) {
            if (isset($data['tax']) && $data['tax'] != $invoice->tax) {
                $data['total_price'] = $data['total_price'] + $data['tax'];
            } else {
                $data['total_price'] = $data['total_price'] + $invoice->tax;
            }
        }
        $this->data = $data;
    }

    protected function calculatePriceInUpdate($invoice, $incomingRecipes)
    {
        $recipesInInvoice = $invoice->recipes;
        foreach ($recipesInInvoice as $recipeOfInvoice) {
            foreach ($incomingRecipes as $recipe) {
                if ($recipeOfInvoice->id == $recipe['recipe_id']) {
                    $recipeOfInvoice->pivot->update([
                        'price' => $recipe['price'],
                        'total_price' => $recipe['price'] * $recipeOfInvoice->pivot->quantity,
                    ]);
                    break;
                }
            }
        }
        $totalPrice = DB::table('invoice_recipe')
            ->where('invoice_id', $invoice->id)
            ->sum('total_price');

        return $totalPrice;
    }

    protected function updatePriceInStore($department, $recipe)
    {
        $store = $department->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot;
        $store->price = $recipe->pivot->price * $recipe->pivot->quantity;
        $store->save();
    }

    // public function updateStoreQuantity2($idStore, $newRecipe, $add, $invoiceId = null)
    // {

    //     Log::info('updateStoreQuantity called', [
    //         'department_store_id' => $idStore,
    //         'newRecipe' => $newRecipe,
    //         'add' => $add,
    //         'invoice_id' => $invoiceId,
    //     ]);



    //     $recipeQuantity = RecipeQuantity::where('department_store_id', $idStore)
    //         ->where('recipe_id', $newRecipe['recipe_id'])
    //         ->when(isset($invoiceId), fn($query) => $query
    //             ->where('invoice_id', $invoiceId))
    //         ->first();

    //     if ($add) {
    //         $remaining = $recipeQuantity?->remaining + $newRecipe['quantity'];
    //         $quantity = $recipeQuantity?->quantity;
    //     } else {
    //         $remaining = $recipeQuantity?->remaining - $newRecipe['quantity'];
    //         $quantity = $recipeQuantity?->quantity - $newRecipe['quantity'];
    //     }

    //     $totalPrice = $recipeQuantity?->price * $remaining;

    //     $recipeQuantity->update([
    //         'remaining' => $remaining,
    //         'total_price' => $totalPrice,
    //         'quantity' => $quantity,
    //     ]);
    // }


    public function updateStoreQuantity($idStore, $newRecipe, $add, $invoiceId = null)
    {
        Log::info('updateStoreQuantity called', [
            'department_store_id' => $idStore,
            'newRecipe' => $newRecipe,
            'add' => $add,
            'invoice_id' => $invoiceId,
        ]);

        $recipeQuantity = RecipeQuantity::where('department_store_id', $idStore)
            ->where('recipe_id', $newRecipe['recipe_id'])
            ->when(isset($invoiceId), fn($query) => $query->where('invoice_id', $invoiceId))
            ->lockForUpdate() // Add row locking for concurrent safety
            ->first();

        $delta = $newRecipe['quantity'];

        // Capture quantity before change for ledger
        $quantityBefore = $recipeQuantity ? $recipeQuantity->remaining : 0;

        $shouldCreate = !$recipeQuantity && (
            ($add && $delta > 0) || (!$add && $delta < 0)
        );

        if ($shouldCreate) {
            $created = RecipeQuantity::create([
                'department_store_id' => $idStore,
                'recipe_id' => $newRecipe['recipe_id'],
                'quantity' => abs($delta),
                'remaining' => abs($delta),
                'price' => $newRecipe['price'] ?? 0,
                'total_price' => ($newRecipe['price'] ?? 0) * abs($delta),
                'expire_date' => $newRecipe['expire_date'] ?? null,
                'invoice_id' => $invoiceId,
            ]);

            Log::info('RecipeQuantity created due to delta-based condition', [
                'id' => $created->id,
                'delta' => $delta,
                'add' => $add,
            ]);

            if (config('app.isInventoryLedgerEnabled', false)) {
                // Record to ledger - NEW
                $this->recordLedgerForStoreUpdate(
                    $idStore,
                    $newRecipe,
                    0, // quantity before
                    abs($delta), // quantity after
                    $invoiceId,
                    $created->id
                );
            }

            return $created;
        }

        if (!$recipeQuantity) {
            Log::error('No matching RecipeQuantity found and create condition not met', [
                'recipe_id' => $newRecipe['recipe_id'],
                'department_store_id' => $idStore,
                'invoice_id' => $invoiceId,
                'delta' => $delta,
                'add' => $add,
            ]);
            throw new \Exception('لا يمكن تعديل الكمية لمكون غير موجود في المخزون');
        }


        if ($add) {
            $remaining = $recipeQuantity->remaining + $delta;
            $quantity = $recipeQuantity->quantity + $delta;
        } else {
            $remaining = $recipeQuantity->remaining - $delta;
            $quantity = $recipeQuantity->quantity - $delta;
        }

        // Safety check - ENABLED
        // if ($remaining < 0 || $quantity < 0) {
        //     Log::error('Negative values after update attempt', [
        //         'id' => $recipeQuantity->id,
        //         'remaining' => $remaining,
        //         'quantity' => $quantity,
        //     ]);
        //     throw new \Exception('الكمية لا يمكن أن تكون سالبة');
        // }

        $totalPrice = $recipeQuantity->price * $remaining;

        $recipeQuantity->update([
            'remaining' => $remaining,
            'total_price' => $totalPrice,
            'quantity' => $quantity,
        ]);

        Log::info('RecipeQuantity updated successfully', [
            'id' => $recipeQuantity->id,
            'remaining' => $remaining,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
        ]);

        if (config('app.isInventoryLedgerEnabled', false)) {
            // Record to ledger - NEW
            $this->recordLedgerForStoreUpdate(
                $idStore,
                $newRecipe,
                $quantityBefore,
                $remaining,
                $invoiceId,
                $recipeQuantity->id
            );
        }
    }

    /**
     * Record ledger entry for store quantity update
     */
    protected function recordLedgerForStoreUpdate(
        $idStore,
        $newRecipe,
        $quantityBefore,
        $quantityAfter,
        $invoiceId = null,
        $recipeQuantityId = null
    ) {
        // Check if ledger system is enabled
        if (!\App\Service\SettingsService::isInventoryLedgerEnabled()) {
            return;
        }

        // Get department ID from store
        $departmentStore = DB::table('department_store')
            ->where('id', $idStore)
            ->first();

        if (!$departmentStore) {
            return;
        }

        // Get invoice if available
        $invoice = $invoiceId ? \App\Models\Invoice::find($invoiceId) : null;

        if ($invoice) {
            $this->ledgerRecorder->record([
                'recipe_id' => $newRecipe['recipe_id'],
                'department_id' => $departmentStore->department_id,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => $invoice->type,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $newRecipe['price'] ?? 0,
                'recipe_quantity_id' => $recipeQuantityId,
                'expire_date' => $newRecipe['expire_date'] ?? null,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
            ]);
        }
    }


    public function updateInvoiceRecipes($invoice, $newRecipe)
    {
        $invoiceRecipe = $invoice->recipes()->wherePivot('recipe_id', $newRecipe['recipe_id'])->first()->pivot;
        $totalPrice = $invoiceRecipe->price * $newRecipe['quantity'];
        $invoiceRecipe->update([
            'quantity' => $newRecipe['quantity'],
            'total_price' => $totalPrice,
        ]);

        return $totalPrice;
    }

    public function updateInvoice($invoice)
    {
        $price = DB::table('invoice_recipe')->where('invoice_id', $invoice->id)->sum('total_price');
        $invoice->update([
            'invoice_price' => $price,
            'total_price' => $price + $invoice->tax - $invoice->discount,
        ]);
    }
}
