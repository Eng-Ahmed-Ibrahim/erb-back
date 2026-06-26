<?php

namespace App\Service\Factory\Invoices\Operators;

use App\DTOs\LedgerCommandDTO;
use App\Models\Invoice;
use App\Service\Factory\Invoices\OperatorInterface;
use App\Service\LedgerProcessor;

class InventoryAdjustmentInvoiceOperator implements OperatorInterface
{
    protected $ledgerProcessor;

    public function __construct(LedgerProcessor $ledgerProcessor)
    {
        $this->ledgerProcessor = $ledgerProcessor;
    }

    /**
     * Build ledger commands for Inventory Adjustment invoice
     * 
     * Adjustments can be positive (add) or negative (remove):
     * - Positive quantity: ADD to inventory
     * - Negative quantity: REMOVE from inventory
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO
     */
    public function buildLedgerCommands(Invoice $invoice): array
    {
        $commands = [];

        foreach ($invoice->recipes as $recipe) {
            $quantity = $recipe->pivot->quantity;
            $isAddition = $quantity > 0;

            if ($isAddition) {
                $commands[] = LedgerCommandDTO::makeDebitCommand([
                    'transaction_type' => 'adjustment',
                    'recipe_id' => $recipe->id,
                    'department_id' => $invoice->from,
                    'quantity' => abs($quantity),
                    'from_department_id' => null,
                    'to_department_id' => $invoice->from,
                    'unit_price' => $recipe->pivot->price,
                    'expire_date' => $recipe->pivot->expire_date,
                    'invoice_id' => $invoice->id,
                    'source_type' => 'invoice',
                    'notes' => "Invoice {$invoice->code} - Inventory adjustment (+)",
                    'metadata' => [
                        'invoice_code' => $invoice->code,
                        'adjustment_type' => 'increase',
                    ],
                ]);
            } else {
                $commands[] = LedgerCommandDTO::makeCreditCommand([
                    'transaction_type' => 'adjustment',
                    'recipe_id' => $recipe->id,
                    'department_id' => $invoice->from,
                    'quantity' => abs($quantity),
                    'from_department_id' => $invoice->from,
                    'to_department_id' => null,
                    'unit_price' => $recipe->pivot->price,
                    'expire_date' => $recipe->pivot->expire_date,
                    'invoice_id' => $invoice->id,
                    'source_type' => 'invoice',
                    'notes' => "Invoice {$invoice->code} - Inventory adjustment (-)",
                    'metadata' => [
                        'invoice_code' => $invoice->code,
                        'adjustment_type' => 'decrease',
                    ],
                ]);
            }
        }

        return $commands;
    }

    /**
     * Validate adjustment invoice
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        if (!$invoice->from) {
            $errors[] = 'Adjustment invoice must have a department';
        }

        // For negative adjustments, check inventory
        foreach ($invoice->recipes as $recipe) {
            if ($recipe->pivot->quantity < 0) {
                $available = $this->ledgerProcessor->getAvailableQuantity(
                    $invoice->from,
                    $recipe->id
                );

                if ($available < abs($recipe->pivot->quantity)) {
                    $errors[] = "Cannot adjust down {$recipe->name}. Available: {$available}, Adjustment: {$recipe->pivot->quantity}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

