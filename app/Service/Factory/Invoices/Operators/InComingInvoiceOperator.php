<?php

namespace App\Service\Factory\Invoices\Operators;

use App\DTOs\LedgerCommandDTO;
use App\Models\Invoice;
use App\Service\Factory\Invoices\OperatorInterface;

class InComingInvoiceOperator implements OperatorInterface
{
    /**
     * Build ledger commands for InComing invoice
     * 
     * InComing invoices are simple: they only ADD to inventory (debit)
     * Supplier → Department (TO)
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO
     */
    public function buildLedgerCommands(Invoice $invoice): array
    {
        $commands = [];

        foreach ($invoice->recipes as $recipe) {
            $commands[] = LedgerCommandDTO::makeDebitCommand([
                'transaction_type' => 'in_coming',
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'quantity' => $recipe->pivot->quantity,
                'from_department_id' => null, // From supplier, not a department
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'invoice_id' => $invoice->id,
                'source_type' => 'invoice',
                'notes' => "Invoice {$invoice->code} - Received from supplier",
                'metadata' => [
                    'invoice_code' => $invoice->code,
                    'supplier_id' => $invoice->supplier_id,
                    'recipe_name' => $recipe->name ?? null,
                ],
            ]);
        }

        return $commands;
    }

    /**
     * Validate if incoming invoice can be processed
     * 
     * InComing invoices have minimal validation:
     * - Must have destination department
     * - Must have at least one recipe
     * - Quantities must be positive
     * 
     * @param Invoice $invoice
     * @return array
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        if (!$invoice->to) {
            $errors[] = 'InComing invoice must have a destination department (to)';
        }

        if ($invoice->recipes->isEmpty()) {
            $errors[] = 'Invoice must have at least one recipe';
        }

        foreach ($invoice->recipes as $recipe) {
            if ($recipe->pivot->quantity <= 0) {
                $errors[] = "Recipe {$recipe->name} has invalid quantity: {$recipe->pivot->quantity}";
            }

            if ($recipe->pivot->price < 0) {
                $errors[] = "Recipe {$recipe->name} has negative price: {$recipe->pivot->price}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

