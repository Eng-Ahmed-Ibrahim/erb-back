<?php

namespace App\Service\Factory\Invoices\Traits;

use App\Models\Invoice;
use App\Service\LedgerProcessor;
use App\Service\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UsesLedgerProcessor
{
    /**
     * Get the operator for this invoice type
     * Must be implemented by the invoice class
     */
    abstract protected function getOperator();

    /**
     * Process invoice via ledger processor
     * 
     * @param Invoice $invoice
     * @return array
     * @throws \Exception
     */
    protected function processViaLedgerProcessor(Invoice $invoice): array
    {
        $operator = $this->getOperator();

        // Validate invoice can be processed
        $validation = $operator->validate($invoice);
        if (!$validation['valid']) {
            throw new \Exception(
                'Invoice validation failed: ' . implode(', ', $validation['errors'])
            );
        }

        // Build ledger commands
        $commands = $operator->buildLedgerCommands($invoice);

        if (empty($commands)) {
            Log::warning('No ledger commands generated for invoice', [
                'invoice_id' => $invoice->id,
                'type' => $invoice->type,
            ]);
            return [
                'status' => true,
                'message' => 'No operations needed',
            ];
        }

        // Process commands through ledger processor
        $ledgerProcessor = app(LedgerProcessor::class);

        try {
            $result = $ledgerProcessor->processBatch($commands);

            Log::info('Invoice processed via ledger', [
                'invoice_id' => $invoice->id,
                'type' => $invoice->type,
                'commands_processed' => count($commands),
                'ledger_entries' => $result['processed_count'],
            ]);

            return [
                'status' => true,
                'message' => 'Invoice processed successfully',
                'ledger_result' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Ledger processing failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if should use ledger processor
     * Now checks system setting instead of property flag
     */
    protected function shouldUseLedgerProcessor(): bool
    {
        // Check system setting first
        $settingEnabled = SettingsService::isInventoryLedgerEnabled();
        
        // For backward compatibility, also check property if setting is not explicitly disabled
        $propertyEnabled = property_exists($this, 'useLedgerProcessor') && $this->useLedgerProcessor === true;
        
        // Both must be true to use ledger processor
        return $settingEnabled && $propertyEnabled;
    }
}

