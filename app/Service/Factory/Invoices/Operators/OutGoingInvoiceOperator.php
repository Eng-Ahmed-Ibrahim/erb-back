<?php

namespace App\Service\Factory\Invoices\Operators;

use App\DTOs\LedgerCommandDTO;
use App\Models\Invoice;
use App\Service\Factory\Invoices\OperatorInterface;
use App\Service\LedgerProcessor;

class OutGoingInvoiceOperator implements OperatorInterface
{
    protected $ledgerProcessor;

    public function __construct(LedgerProcessor $ledgerProcessor)
    {
        $this->ledgerProcessor = $ledgerProcessor;
    }

    /**
     * Build ledger commands for OutGoing invoice
     * 
     * OutGoing invoices transfer between departments:
     * 1. REMOVE from source department (credit)
     * 2. ADD to destination department (debit)
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO
     */
    public function buildLedgerCommands(Invoice $invoice): array
    {
        $commands = [];

        foreach ($invoice->recipes as $recipe) {
            // Command 1: Credit from source department (use FIFO - no specific invoice)
            $commands[] = LedgerCommandDTO::makeCreditCommand([
                'transaction_type' => 'out_going',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => $recipe->pivot->source_invoice_id ?? null,
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Transferred out",
                'metadata' => [
                    'transfer_invoice_id' => $invoice->id, // Track which transfer invoice caused this
                    'invoice_code' => $invoice->code,
                    'recipe_name' => $recipe->name ?? null,
                    'transfer_direction' => 'outbound',
                    'source_invoice_id' => $recipe->pivot->source_invoice_id ?? null,
                ],
            ]);

            // Command 2: Debit to destination department (new batch with transfer invoice_id)
            $commands[] = LedgerCommandDTO::makeDebitCommand([
                'transaction_type' => 'out_going',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => $invoice->id,
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Received from transfer",
                'metadata' => [
                    'invoice_code' => $invoice->code,
                    'recipe_name' => $recipe->name ?? null,
                    'transfer_direction' => 'inbound',
                    'source_invoice_id' => $recipe->pivot->source_invoice_id ?? null,
                ],
            ]);
        }

        return $commands;
    }

    /**
     * Validate if outgoing invoice can be processed
     * 
     * OutGoing invoices need:
     * - Source AND destination departments
     * - Sufficient inventory at source
     * - Valid quantities
     * 
     * @param Invoice $invoice
     * @return array
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        if (!$invoice->from) {
            $errors[] = 'OutGoing invoice must have a source department (from)';
        }

        if (!$invoice->to) {
            $errors[] = 'OutGoing invoice must have a destination department (to)';
        }

        if ($invoice->from === $invoice->to) {
            $errors[] = 'Source and destination departments cannot be the same';
        }

        if ($invoice->recipes->isEmpty()) {
            $errors[] = 'Invoice must have at least one recipe';
        }

        // Check inventory availability
        foreach ($invoice->recipes as $recipe) {
            if ($recipe->pivot->quantity <= 0) {
                $errors[] = "Recipe {$recipe->name} has invalid quantity: {$recipe->pivot->quantity}";
                continue;
            }

            $available = $this->ledgerProcessor->getAvailableQuantity(
                $invoice->from,
                $recipe->id
            );

            if ($available < $recipe->pivot->quantity) {
                $errors[] = "Insufficient inventory for {$recipe->name}. Available: {$available}, Requested: {$recipe->pivot->quantity}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

