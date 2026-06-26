<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\DepartmentStore;
use App\Service\InventoryLedgerRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeLedger extends Command
{
    protected $signature = 'inventory:init-ledger
                            {--force : Force initialization and clear existing entries}
                            {--from-date= : Only process invoices from this date onwards}';

    protected $description = 'Initialize ledger by replaying all historical invoices and orders';

    protected $ledgerRecorder;
    protected $stats = [
        'cleared' => 0,
        'invoices_processed' => 0,
        'orders_processed' => 0,
        'entries_created' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->info('🚀 Initializing Inventory Ledger from Historical Invoices and Orders...');
        $this->newLine();

        // Check if ledger already has entries
        $existingEntries = DB::table('inventory_ledger')->count();
        
        if ($existingEntries > 0) {
            $this->warn("⚠️  Ledger currently has {$existingEntries} entries.");
            
            if (!$this->option('force')) {
                $this->error('Use --force flag to clear and rebuild the ledger.');
                return 1;
            }

            if (!$this->confirm('This will DELETE all existing ledger entries and rebuild from invoices. Continue?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            // Clear existing ledger
            $this->info('Clearing existing ledger entries...');
            DB::table('inventory_ledger')->delete();
            $this->stats['cleared'] = $existingEntries;
            $this->info("✓ Cleared {$existingEntries} entries");
            $this->newLine();
        }

        $this->ledgerRecorder = app(InventoryLedgerRecorder::class);

        // Get all invoices ordered by date
        $query = Invoice::with(['recipes', 'fromDepartment', 'toDepartment'])
            ->orderBy('created_at', 'asc');

        if ($this->option('from-date')) {
            $query->where('created_at', '>=', $this->option('from-date'));
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->warn('No invoices found to process.');
            return 0;
        }

        $this->info("Found {$invoices->count()} invoices to process...");
        $this->newLine();

        // Process invoices
        $this->info('Processing invoices...');
        $this->withProgressBar($invoices, function ($invoice) {
            $this->processInvoice($invoice);
        });

        $this->newLine(2);
        $this->info("✓ Processed {$this->stats['invoices_processed']} invoices");
        $this->newLine();

        // Now process orders
        $this->processOrders();

        $this->newLine();
        $this->displayResults();

        return 0;
    }

    protected function processInvoice($invoice)
    {
        try {
            // Process based on invoice type
            switch ($invoice->type) {
                case 'in_coming':
                    $this->processInComingInvoice($invoice);
                    break;
                
                case 'out_going':
                    $this->processOutGoingInvoice($invoice);
                    break;
                
                case 'returned':
                    $this->processReturnedInvoice($invoice);
                    break;
                
                case 'transfare':
                    $this->processTransfareInvoice($invoice);
                    break;
                
                case 'tainted':
                    $this->processTaintedInvoice($invoice);
                    break;
                
                default:
                    \Log::warning("Unknown invoice type: {$invoice->type}", ['invoice_id' => $invoice->id]);
            }

            $this->stats['invoices_processed']++;
        } catch (\Exception $e) {
            $this->stats['errors']++;
            \Log::error('Failed to process invoice', [
                'invoice_id' => $invoice->id,
                'type' => $invoice->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function processInComingInvoice($invoice)
    {
        // InComing invoice: Supplier → Department (TO)
        foreach ($invoice->recipes as $recipe) {
            $this->createLedgerEntry([
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'entry_type' => 'debit', // Adding to inventory
                'quantity_before' => 0, // We don't have historical before values
                'quantity_after' => $recipe->pivot->quantity,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => 'in_coming',
                'from_department_id' => null,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'notes' => "Invoice {$invoice->code} - Received from supplier",
            ]);
        }
    }

    protected function processOutGoingInvoice($invoice)
    {
        // OutGoing invoice: Department (FROM) → Department (TO)
        foreach ($invoice->recipes as $recipe) {
            // Credit from source department (removing)
            $this->createLedgerEntry([
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'entry_type' => 'credit',
                'quantity_before' => $recipe->pivot->quantity,
                'quantity_after' => 0,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => 'out_going',
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'notes' => "Invoice {$invoice->code} - Transferred out",
            ]);

            // Debit to destination department (adding)
            $this->createLedgerEntry([
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'entry_type' => 'debit',
                'quantity_before' => 0,
                'quantity_after' => $recipe->pivot->quantity,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => 'out_going',
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'notes' => "Invoice {$invoice->code} - Received from transfer",
            ]);
        }
    }

    protected function processReturnedInvoice($invoice)
    {
        // Returned invoice: Department (FROM) returns to Department (TO)
        foreach ($invoice->recipes as $recipe) {
            // Credit from source (returning from)
            $this->createLedgerEntry([
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'entry_type' => 'credit',
                'quantity_before' => $recipe->pivot->quantity,
                'quantity_after' => 0,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => 'returned',
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'notes' => "Invoice {$invoice->code} - Returned items",
            ]);

            // Debit to destination (receiving return)
            $this->createLedgerEntry([
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->to,
                'entry_type' => 'debit',
                'quantity_before' => 0,
                'quantity_after' => $recipe->pivot->quantity,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => 'returned',
                'from_department_id' => $invoice->from,
                'to_department_id' => $invoice->to,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'notes' => "Invoice {$invoice->code} - Received return",
            ]);
        }
    }

    protected function processTransfareInvoice($invoice)
    {
        // Transfare (same as OutGoing)
        $this->processOutGoingInvoice($invoice);
    }

    protected function processTaintedInvoice($invoice)
    {
        // Tainted invoice: Department (FROM) → Waste
        foreach ($invoice->recipes as $recipe) {
            $this->createLedgerEntry([
                'recipe_id' => $recipe->id,
                'department_id' => $invoice->from,
                'entry_type' => 'credit', // Removing from inventory
                'quantity_before' => $recipe->pivot->quantity,
                'quantity_after' => 0,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'transaction_type' => 'tainted',
                'from_department_id' => $invoice->from,
                'to_department_id' => null,
                'unit_price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'notes' => "Invoice {$invoice->code} - Tainted/Damaged items removed",
            ]);
        }
    }

    protected function createLedgerEntry($data)
    {
        try {
            $this->ledgerRecorder->record($data);
            $this->stats['entries_created']++;
        } catch (\Exception $e) {
            $this->stats['errors']++;
            throw $e;
        }
    }

    protected function processOrders()
    {
        $this->info('Processing historical orders...');

        $query = Order::with(['products.product.recipes', 'department'])
            ->whereIn('status', ['closed', 'completed'])
            ->orderBy('created_at', 'asc');

        if ($this->option('from-date')) {
            $query->where('created_at', '>=', $this->option('from-date'));
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->warn('No closed/completed orders found to process.');
            return;
        }

        $this->info("Found {$orders->count()} orders to process...");

        $this->withProgressBar($orders, function ($order) {
            $this->processOrder($order);
        });

        $this->newLine();
        $this->info("✓ Processed {$this->stats['orders_processed']} orders");
    }

    protected function processOrder($order)
    {
        try {
            foreach ($order->products as $orderProduct) {
                $product = $orderProduct->product;
                
                if (!$product || !$product->recipes) {
                    continue;
                }

                foreach ($product->recipes as $recipe) {
                    $quantityConsumed = $recipe->pivot->quantity * $orderProduct->quantity;

                    // Create consumption ledger entry
                    $this->createLedgerEntry([
                        'recipe_id' => $recipe->id,
                        'department_id' => $order->department_id,
                        'entry_type' => 'credit', // Removing from inventory
                        'quantity_before' => $quantityConsumed, // We don't have exact before value
                        'quantity_after' => 0,
                        'source_type' => 'order',
                        'source_id' => $order->id,
                        'transaction_type' => 'consumption',
                        'unit_price' => 0, // Orders don't track unit prices for recipes
                        'transaction_date' => $order->created_at,
                        'notes' => "Order {$order->code} - Product: {$product->name} (Qty: {$orderProduct->quantity})",
                        'metadata' => [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'order_product_id' => $orderProduct->id,
                            'product_quantity' => $orderProduct->quantity,
                            'recipe_quantity_per_product' => $recipe->pivot->quantity,
                        ],
                    ]);
                }
            }

            $this->stats['orders_processed']++;
        } catch (\Exception $e) {
            $this->stats['errors']++;
            \Log::error('Failed to process order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function displayResults()
    {
        $this->line("═══════════════════════════════════════");
        $this->info("LEDGER INITIALIZATION COMPLETE");
        $this->line("═══════════════════════════════════════");
        $this->newLine();
        
        if ($this->stats['cleared'] > 0) {
            $this->line("Cleared Entries: {$this->stats['cleared']}");
        }
        $this->info("Invoices Processed: {$this->stats['invoices_processed']}");
        $this->info("Orders Processed: {$this->stats['orders_processed']}");
        $this->info("Ledger Entries Created: {$this->stats['entries_created']}");
        
        if ($this->stats['errors'] > 0) {
            $this->error("Errors: {$this->stats['errors']}");
            $this->warn("Check logs for details: storage/logs/laravel.log");
        } else {
            $this->info("Errors: 0");
        }
        
        $this->line("═══════════════════════════════════════");
        $this->newLine();
        
        $this->info('✅ Ledger rebuilt from historical invoices AND orders!');
        $this->info('💡 Run "php artisan inventory:validate" to verify accuracy.');
    }
}

