<?php

namespace App\Service\Factory\Invoices\Operators;

use App\DTOs\LedgerCommandDTO;
use App\Models\Invoice;
use App\Service\Factory\Invoices\OperatorInterface;
use App\Service\LedgerProcessor;

class TaintedInvoiceOperator implements OperatorInterface
{
    protected $ledgerProcessor;

    public function __construct(LedgerProcessor $ledgerProcessor)
    {
        $this->ledgerProcessor = $ledgerProcessor;
    }

    /**
     * Build ledger commands for Tainted invoice
     * 
     * Tainted invoices remove damaged/expired items from inventory:
     * Single command per recipe: REMOVE from department (no destination)
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO
     */
    public function buildLedgerCommands(Invoice $invoice): array
    {
        $commands = [];

        foreach ($invoice->recipes as $recipe) {
            $commands[] = LedgerCommandDTO::makeCreditCommand([
                'transaction_type' => 'tainted',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => $invoice->from,
                'to_department_id' => null, // No destination - removed from system
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => null, // ← NULL = use FIFO (remove oldest/damaged first)
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Tainted/Damaged items removed",
                'metadata' => [
                    'tainted_invoice_id' => $invoice->id, // Track which tainted invoice caused this
                    'invoice_code' => $invoice->code,
                    'recipe_name' => $recipe->name ?? null,
                    'reason' => 'tainted',
                ],
            ]);
        }

        return $commands;
    }

    /**
     * Validate tainted invoice
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        if (!$invoice->from) {
            $errors[] = 'Tainted invoice must have a source department';
        }

        // Check inventory availability
        foreach ($invoice->recipes as $recipe) {
            $available = $this->ledgerProcessor->getAvailableQuantity(
                $invoice->from,
                $recipe->id
            );

            if ($available < $recipe->pivot->quantity) {
                $errors[] = "Insufficient inventory to mark as tainted for {$recipe->name}. Available: {$available}, Requested: {$recipe->pivot->quantity}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

