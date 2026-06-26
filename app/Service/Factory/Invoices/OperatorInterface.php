<?php

namespace App\Service\Factory\Invoices;

use App\Models\Invoice;
use App\DTOs\LedgerCommandDTO;

interface OperatorInterface
{
    /**
     * Build ledger commands from an invoice
     * 
     * Each invoice type knows how to translate itself into ledger operations.
     * Returns an array of LedgerCommandDTO objects that describe
     * what should happen to inventory.
     * 
     * @param Invoice $invoice
     * @return array Array of LedgerCommandDTO objects
     */
    public function buildLedgerCommands(Invoice $invoice): array;

    /**
     * Validate if the invoice can be processed
     * Check inventory availability, business rules, etc.
     * 
     * @param Invoice $invoice
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(Invoice $invoice): array;
}

