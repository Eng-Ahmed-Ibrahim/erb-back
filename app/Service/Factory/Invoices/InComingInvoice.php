<?php

namespace App\Service\Factory\Invoices;

use App\Models\InventoryArchive;
use App\Models\RecipeQuantity;
use App\Service\Factory\AbstractInvoice;
use App\Service\Factory\InvoiceInterface;
use App\Service\Factory\Invoices\Traits\UsesLedgerProcessor;
use App\Service\Factory\Invoices\Operators\InComingInvoiceOperator;
use App\Service\InventoryLedgerRecorder;
use Carbon\carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InComingInvoice extends AbstractInvoice implements InvoiceInterface
{
    use UsesLedgerProcessor;

    protected $useLedgerProcessor = true; // Feature flag - set to true to use new system

    public function __construct()
    {
        parent::__construct();
    }


    protected function getOperator()
    {
        return app(InComingInvoiceOperator::class);
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
                $status = $this->createEnterInvoice($invoice);
                if ($status['status'] == false || !$invoice) {
                    DB::rollBack();
                    return $status;
                }
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InComing invoice creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateInvoicePrices($invoice, $data)
    {
        $this->processUpdateData($invoice, $data);

        $this->invoiceRepositry->adminUpdate($invoice, $this->data);

        $this->updateEnterInvoice($invoice, $this->data);

        return $invoice;
    }

    public function updateInvoiceQuantity($invoice, $data)
    {
        $toDepartment = $invoice->toDepartment;
        $oldRecipes = $invoice->recipes;
        DB::beginTransaction();
        try {
            if (config('app.isInventoryLedgerEnabled', false)) {
                // NEW: Use ledger processor for updates
                $operator = $this->getOperator();
                $processor = app(\App\Service\LedgerProcessor::class);

                foreach ($data['recipes'] as $newRecipe) {
                    foreach ($oldRecipes as $oldRecipe) {
                        if ($newRecipe['recipe_id'] == $oldRecipe['id']) {
                            $this->updateInvoiceRecipes($invoice, $newRecipe);

                            // Calculate difference
                            $oldQuantity = $oldRecipe->pivot->quantity;
                            $newQuantity = $newRecipe['quantity'];
                            $difference = $newQuantity - $oldQuantity;

                            if ($difference != 0) {
                                // Create adjustment command for the difference
                                if ($difference > 0) {
                                    // Need to add more
                                    $command = \App\DTOs\LedgerCommandDTO::makeDebitCommand([
                                        'transaction_type' => 'in_coming',
                                        'recipe_id' => $newRecipe['recipe_id'],
                                        'department_id' => $invoice->to,
                                        'quantity' => abs($difference),
                                        'from_department_id' => null,
                                        'to_department_id' => $invoice->to,
                                        'unit_price' => $newRecipe['price'] ?? $oldRecipe->pivot->price,
                                        'expire_date' => $newRecipe['expire_date'] ?? $oldRecipe->pivot->expire_date,
                                        'invoice_id' => $invoice->id,
                                        'source_type' => 'invoice',
                                        'notes' => "Invoice {$invoice->code} - Quantity update (+{$difference})",
                                    ]);
                                } else {
                                    // Need to remove
                                    $command = \App\DTOs\LedgerCommandDTO::makeCreditCommand([
                                        'transaction_type' => 'in_coming',
                                        'recipe_id' => $newRecipe['recipe_id'],
                                        'department_id' => $invoice->to,
                                        'quantity' => abs($difference),
                                        'from_department_id' => $invoice->to,
                                        'to_department_id' => null,
                                        'unit_price' => $newRecipe['price'] ?? $oldRecipe->pivot->price,
                                        'expire_date' => $newRecipe['expire_date'] ?? $oldRecipe->pivot->expire_date,
                                        'invoice_id' => $invoice->id,
                                        'source_type' => 'invoice',
                                        'notes' => "Invoice {$invoice->code} - Quantity update ({$difference})",
                                    ]);
                                }

                                $processor->process($command);
                            }

                            $this->createOrUpdatePivot($toDepartment, $oldRecipe);
                        }
                    }
                }
            } else {
                // LEGACY: Old update logic
                foreach ($data['recipes'] as $newRecipe) {
                    foreach ($oldRecipes as $oldRecipe) {
                        if ($newRecipe['recipe_id'] == $oldRecipe['id']) {
                            $checkpoint = $oldRecipe->pivot->quantity - $newRecipe['quantity'];
                            $this->updateInvoiceRecipes($invoice, $newRecipe);
                            $newRecipe['quantity'] = $checkpoint;

                            $storeToId = $this->getPivotId($toDepartment, $oldRecipe);
                            $this->updateStoreQuantity($storeToId, $newRecipe, false, $invoice->id);
                            $this->createOrUpdatePivot($toDepartment, $oldRecipe);

                            // SENCE THE DIFF
                            $diffrence = $newRecipe['quantity'] - $oldRecipe['quantity'];
                            $diffrence = (float) $diffrence;

                            // FETCH ALL RECORDS TO BE UPDATES, THEN UPDATE
                            InventoryArchive::where('recipe_id', '=', $newRecipe['recipe_id'])
                                ->where('department_id', '=', $invoice->to)
                                ->where('captured_at', '>=', $invoice->created_at)
                                ->where('captured_at', '<=', carbon::now())
                                ->update([
                                    'quantity' => \DB::raw('quantity + ' . $diffrence),
                                    'updated_at' => carbon::now(),
                                ]);
                        }
                    }
                }
            }

            if (config('app.isInventoryLedgerEnabled', false)) {
                // Update inventory archive for both paths
                foreach ($data['recipes'] as $newRecipe) {
                    foreach ($oldRecipes as $oldRecipe) {
                        if ($newRecipe['recipe_id'] == $oldRecipe['id']) {
                            $diffrence = $newRecipe['quantity'] - $oldRecipe->pivot->quantity;
                            $diffrence = (float) $diffrence;

                            InventoryArchive::where('recipe_id', '=', $newRecipe['recipe_id'])
                                ->where('department_id', '=', $invoice->to)
                                ->where('captured_at', '>=', $invoice->created_at)
                                ->where('captured_at', '<=', carbon::now())
                                ->update([
                                    'quantity' => \DB::raw('quantity + ' . $diffrence),
                                    'updated_at' => carbon::now(),
                                ]);
                        }

                    }
                }
            }
            DB::commit();

            // get all records , fetch the
            $this->updateInvoice($invoice);

            return [
                'status' => true,
                'message' => 'تم تعديل الفاتورة بنجاح',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('InComing invoice update failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id
            ]);
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createEnterInvoice($invoice)
    {
        $toDepartment = $invoice->toDepartment;
        $recipes = $invoice->recipes;
        foreach ($recipes as $recipe) {
            $pivotFlag = $this->createPivotIfNotExist($toDepartment, $recipe);
            $flag = $this->createOrUpdateRecipeQuantity($toDepartment, $recipe, $invoice->id);
            if (!$flag || !$pivotFlag) {
                return [
                    'status' => false,
                    'message' => 'عفوا حدث خطأ ما اثناء تسجيل الفاتورة ',
                ];
            }
            $this->createOrUpdatePivot($toDepartment, $recipe);
        }

        return [
            'status' => true,
            'message' => 'تم تسجيل فاتورة المورد بنجاح',
        ];
    }

    public function updateEnterInvoice($invoice, $data)
    {
        $toDepartment = $invoice->toDepartment;

        $recipes = $invoice->recipes;
        $newRecipes = $data['recipes'];

        foreach ($newRecipes as $newRecipe) {
            foreach ($recipes as $oldRecipe) {
                if ($oldRecipe->id == $newRecipe['recipe_id']) {
                    $this->updateInvoiceRecipe($invoice, $newRecipe);
                    $this->updateRecipeQuantite($toDepartment, $oldRecipe, $invoice->id);

                    $this->createOrUpdatePivot($toDepartment, $oldRecipe);
                    break;
                }
            }
        }
    }

    public function updateInvoiceRecipe($invoice, $recipe)  // **
    {
        $invoiceRecipe = $invoice->recipes()->wherePivot('recipe_id', $recipe['recipe_id'])->first()->pivot;
        $invoiceRecipe->update([
            'price' => $recipe['price'],
            'total_price' => $recipe['price'] * $invoiceRecipe->quantity,
        ]);
    }

    public function processCreateData($data)
    {
        $department = auth('api')->user()->department;
        $data['to'] = $department->id;
        $data['from'] = null;
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

    protected function updateRecipeQuantite($department, $recipe, $invoiceId)  // **
    {
        $deparmentStoreId = $this->getPivotId($department, $recipe);

        RecipeQuantity::where('department_store_id', $deparmentStoreId)
            ->where('recipe_id', $recipe->id)
            ->where('invoice_id', $invoiceId)
            ->update([
                'price' => $recipe->pivot->price,
                'total_price' => $recipe->pivot->price * $recipe->pivot->quantity,
            ]);
    }
}
