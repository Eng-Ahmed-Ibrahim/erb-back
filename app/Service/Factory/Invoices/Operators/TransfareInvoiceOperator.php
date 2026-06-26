<?php

namespace App\Service\Factory\Invoices\Operators;

use App\DTOs\LedgerCommandDTO;
use App\Models\Invoice;
use App\Service\Factory\Invoices\OperatorInterface;
use App\Service\LedgerProcessor;

class TransfareInvoiceOperator implements OperatorInterface
{
    protected $ledgerProcessor;

    public function __construct(LedgerProcessor $ledgerProcessor)
    {
        $this->ledgerProcessor = $ledgerProcessor;
    }

    /**
     * Build ledger commands for Transfer invoice
     * 
     * Transfers are lateral movements between departments:
     * 1. REMOVE from source department
     * 2. ADD to destination department
     * 
     * Same as OutGoing but different semantic meaning
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO
     */
    public function buildLedgerCommands(Invoice $invoice): array
    {
        $commands = [];

        foreach ($invoice->recipes as $recipe) {
            // Command 1: Credit from source (use FIFO)
            $commands[] = LedgerCommandDTO::makeCreditCommand([
                'transaction_type' => 'transfare',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => null, // ← NULL = use FIFO
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Transferred out",
                'metadata' => [
                    'transfer_invoice_id' => $invoice->id,
                ],
            ]);

            // Command 2: Debit to destination
            $commands[] = LedgerCommandDTO::makeDebitCommand([
                'transaction_type' => 'transfare',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => $invoice->id,
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Received via transfer",
            ]);
        }

        return $commands;
    }

    /**
     * Validate transfer invoice
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        if (!$invoice->from) {
            $errors[] = 'Transfer invoice must have a source department';
        }

        if (!$invoice->to) {
            $errors[] = 'Transfer invoice must have a destination department';
        }

        if ($invoice->from === $invoice->to) {
            $errors[] = 'Cannot transfer to the same department';
        }

        // Check inventory availability
        foreach ($invoice->recipes as $recipe) {
            $available = $this->ledgerProcessor->getAvailableQuantity(
                $invoice->from,
                $recipe->id
            );

            if ($available < $recipe->pivot->quantity) {
                $errors[] = "Insufficient inventory for {$recipe->name}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

