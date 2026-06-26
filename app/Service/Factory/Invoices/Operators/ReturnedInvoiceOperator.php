<?php

namespace App\Service\Factory\Invoices\Operators;

use App\DTOs\LedgerCommandDTO;
use App\Models\Invoice;
use App\Service\Factory\Invoices\OperatorInterface;
use App\Service\LedgerProcessor;

class ReturnedInvoiceOperator implements OperatorInterface
{
    protected $ledgerProcessor;

    public function __construct(LedgerProcessor $ledgerProcessor)
    {
        $this->ledgerProcessor = $ledgerProcessor;
    }

    /**
     * Build ledger commands for Returned invoice
     * 
     * Returns are reverse transfers:
     * 1. REMOVE from returning department (from)
     * 2. ADD to receiving department (to)
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO
     */
    public function buildLedgerCommands(Invoice $invoice): array
    {
        $commands = [];

        foreach ($invoice->recipes as $recipe) {
            // Command 1: Credit from returning department (use FIFO unless specific return)
            $commands[] = LedgerCommandDTO::makeCreditCommand([
                'transaction_type' => 'returned',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => $recipe->pivot->source_invoice_id ?? null, // ← link to original invoice when available
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Returned items",
                'metadata' => [
                    'return_invoice_id' => $invoice->id,
                    'source_invoice_id' => $recipe->pivot->source_invoice_id ?? null,
                ],
            ]);

            // Command 2: Debit to receiving department
            $commands[] = LedgerCommandDTO::makeDebitCommand([
                'transaction_type' => 'returned',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => $recipe->pivot->source_invoice_id ?? $invoice->id,
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Received return",
                'metadata' => [
                    'return_invoice_id' => $invoice->id,
                    'source_invoice_id' => $recipe->pivot->source_invoice_id ?? null,
                ],
            ]);
        }

        return $commands;
    }

    /**
     * Validate returned invoice
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        if (!$invoice->from) {
            $errors[] = 'Returned invoice must have a source department (from)';
        }

        if (!$invoice->to) {
            $errors[] = 'Returned invoice must have a destination department (to)';
        }

        // Check inventory availability at source
        foreach ($invoice->recipes as $recipe) {
            $available = $this->ledgerProcessor->getAvailableQuantity(
                $invoice->from,
                $recipe->id
            );

            if ($available < $recipe->pivot->quantity) {
                $errors[] = "Insufficient inventory to return {$recipe->name}. Available: {$available}, Requested: {$recipe->pivot->quantity}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

