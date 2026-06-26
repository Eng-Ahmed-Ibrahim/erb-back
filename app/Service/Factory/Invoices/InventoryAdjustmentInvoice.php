<?php

namespace App\Service\Factory\Invoices;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\RecipeQuantity;
use App\Service\Factory\AbstractInvoice;
use App\Service\Factory\InvoiceInterface;
use App\Service\Factory\Invoices\Traits\UsesLedgerProcessor;
use App\Service\Factory\Invoices\Operators\InventoryAdjustmentInvoiceOperator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryAdjustmentInvoice extends AbstractInvoice implements InvoiceInterface
{
    use UsesLedgerProcessor;

    protected $useLedgerProcessor = true; // Feature flag

    public function __construct()
    {
        parent::__construct();
    }

    protected function getOperator()
    {
        return app(InventoryAdjustmentInvoiceOperator::class);
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
            Log::error('Inventory adjustment creation failed', ['error' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function processCreateData($data)
    {
        $department = Department::find($data['from']);
        $data['supplier_id'] = null;
        $data['for'] = null;
        $data['code'] = $this->generateCode($this->invoiceRepositry);
        $data['invoice_date'] = $data['invoice_date'] ?? now();

        foreach ($data['recipes'] as &$recipe) {
            $recipe['price'] = $recipe['price'] ?? 0;
            // $this->getAvaragePrice($recipe, $department)['price'];

            // Only get expire_date if not already set
            if (!isset($recipe['expire_date']) || $recipe['expire_date'] === null) {
                $recipe['expire_date'] = $this->getExpireDate($department, $recipe);
            }

            // Fallback to default expire_date if still null
            if ($recipe['expire_date'] === null) {
                $recipe['expire_date'] = now()->addYear()->format('Y-m-d');
            }
        }

        $data['status'] = 'pending';
        $data['is_paid'] = 0;
        $data['is_closed'] = 0;
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
        $quantity = $recipe['quantity'];

        $store = DB::table('department_store')->where('recipe_id', '=', $recipe['recipe_id'])->where('department_id', '=', $department->id)->first();

        $recipesQuantites = RecipeQuantity::where('department_store_id', $store->id)
            ->where('recipe_id', $recipe['recipe_id'])
            ->when(isset($recipe['invoice_id']) && $recipe['invoice_id'] != '', fn($query) => $query
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

        $recipes = $invoice->recipes;

        DB::beginTransaction();

        foreach ($recipes as $recipe) {
            DB::beginTransaction();

            $quantity = $recipe->pivot->quantity ?? 0;

            $this->UpdateRecipeQuantites($fromDepartment, $invoice->id, $recipe, $quantity);
            $this->createOrUpdatePivot($fromDepartment, $recipe);
            DB::commit();
        }
        DB::commit();

        return [
            'status' => true,
            'message' => 'تم حفظ الجرد بنجاح',
        ];
    }

    public function UpdateRecipeQuantites($fromDepartment, $invoiceId, $recipe, $quantity)
    {
        $fromPivotId = $this->getPivotId($fromDepartment, $recipe);
        if ($quantity > 0) {
            RecipeQuantity::create([
                'recipe_id' => $recipe->id,
                'expire_date' => $recipe->pivot->expire_date,
                'price' => $recipe->pivot->price,
                'quantity' => $recipe->pivot->quantity,
                'remaining' => $recipe->pivot->quantity,
                'invoice_id' => $invoiceId,
                'total_price' => $recipe->pivot->price * $recipe->pivot->quantity,
                'department_store_id' => $fromPivotId,
            ]);
        } else {
            $quantity = abs($quantity);
            $quantites = RecipeQuantity::where('department_store_id', $fromPivotId)
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
            ->when(isset($recipe['invoice_id']) && $recipe['invoice_id'] != '', fn($query) => $query
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

    public function updateInvoicePrices($invoice, $data)
    {
        return [
            'status' => false,
            'message' => 'لا يمكن تعديل اسعار اذون الصرف',
        ];
    }

    public function updateInvoiceQuantity($invoice, $data)
    {
        return [
            'status' => false,
            'message' => 'لا يمكن تعديل اسعار اذون الصرف',
        ];
    }
}
