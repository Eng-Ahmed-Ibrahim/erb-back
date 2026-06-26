<?php

namespace App\Service;

use App\DTOs\LedgerCommandDTO;
use App\Models\DepartmentStore;
use App\Models\InventoryLedger;
use App\Models\RecipeQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LedgerProcessor
{
    protected $ledgerRecorder;

    public function __construct(InventoryLedgerRecorder $ledgerRecorder)
    {
        $this->ledgerRecorder = $ledgerRecorder;
    }

    /**
     * Process a single ledger command
     * 
     * @param LedgerCommandDTO $command
     * @return array
     * @throws \Exception
     */
    public function process(LedgerCommandDTO $command): array
    {
        // Validate command
        if (!$command->isValid()) {
            throw new \InvalidArgumentException(
                'Invalid ledger command: ' . implode(', ', $command->validate())
            );
        }

        return DB::transaction(function () use ($command) {
            $result = [
                'success' => true,
                'ledger_entries' => [],
                'batches_affected' => [],
            ];

            if ($command->entryType === 'debit') {
                $result = $this->applyDebit($command);
            } else {
                $result = $this->applyCredit($command);
            }

            // Update department_store aggregate
            $this->updateDepartmentStore($command->departmentId, $command->recipeId);

            return $result;
        });
    }

    /**
     * Process multiple commands in batch (all or nothing)
     * 
     * @param array $commands Array of LedgerCommandDTO
     * @return array
     */
    public function processBatch(array $commands): array
    {
        return DB::transaction(function () use ($commands) {
            $results = [];

            foreach ($commands as $command) {
                if (!($command instanceof LedgerCommandDTO)) {
                    throw new \InvalidArgumentException('All commands must be LedgerCommandDTO instances');
                }

                $results[] = $this->process($command);
            }

            return [
                'success' => true,
                'processed_count' => count($results),
                'results' => $results,
            ];
        });
    }

    /**
     * Apply debit to inventory (adding)
     * 
     * @param LedgerCommandDTO $command
     * @return array
     */
    protected function applyDebit(LedgerCommandDTO $command): array
    {
        $departmentStoreId = $this->getOrCreateDepartmentStore(
            $command->departmentId,
            $command->recipeId
        );

        // Get current quantity for ledger recording
        $currentQuantity = $this->getCurrentQuantity($command->departmentId, $command->recipeId);

        // Check if recipe_quantity with same attributes exists
        $existingBatch = RecipeQuantity::where('department_store_id', $departmentStoreId)
            ->where('recipe_id', $command->recipeId)
            ->where('invoice_id', $command->invoiceId)
            ->where('expire_date', $command->expireDate)
            ->where('price', $command->unitPrice)
            ->lockForUpdate()
            ->first();

        if ($existingBatch) {
            // Update existing batch
            $existingBatch->update([
                'quantity' => $existingBatch->quantity + $command->quantity,
                'remaining' => $existingBatch->remaining + $command->quantity,
                'total_price' => ($existingBatch->remaining + $command->quantity) * $existingBatch->price,
            ]);

            $batchId = $existingBatch->id;
        } else {
            // Create new batch
            $newBatch = RecipeQuantity::create([
                'recipe_id' => $command->recipeId,
                'department_store_id' => $departmentStoreId,
                'invoice_id' => $command->invoiceId,
                'expire_date' => $command->expireDate,
                'price' => $command->unitPrice,
                'quantity' => $command->quantity,
                'remaining' => $command->quantity,
                'total_price' => $command->quantity * $command->unitPrice,
            ]);

            $batchId = $newBatch->id;
        }

        // Record to ledger
        $ledgerEntry = $this->ledgerRecorder->record([
            'recipe_id' => $command->recipeId,
            'department_id' => $command->departmentId,
            'quantity_before' => $currentQuantity,
            'quantity_after' => $currentQuantity + $command->quantity,
            'entry_type' => 'debit',
            'source_type' => $command->sourceType,
            'source_id' => $command->invoiceId ?? $command->orderId,
            'transaction_type' => $command->transactionType,
            'from_department_id' => $command->fromDepartmentId,
            'to_department_id' => $command->toDepartmentId,
            'unit_price' => $command->unitPrice,
            'recipe_quantity_id' => $batchId,
            'expire_date' => $command->expireDate,
            'notes' => $command->notes,
            'metadata' => $command->metadata,
        ]);

        return [
            'success' => true,
            'entry_type' => 'debit',
            'quantity_added' => $command->quantity,
            'ledger_entry_id' => $ledgerEntry->id,
            'batch_id' => $batchId,
        ];
    }

    /**
     * Apply credit to inventory (removing)
     * 
     * @param LedgerCommandDTO $command
     * @return array
     */
    protected function applyCredit(LedgerCommandDTO $command): array
    {
        $departmentStoreId = $this->getOrCreateDepartmentStore(
            $command->departmentId,
            $command->recipeId
        );

        // Get current quantity for ledger recording
        $currentQuantity = $this->getCurrentQuantity($command->departmentId, $command->recipeId);

        // Check if we have enough inventory
        if ($currentQuantity < $command->quantity) {
            throw new \Exception(
                "Insufficient inventory. Available: {$currentQuantity}, Requested: {$command->quantity}"
            );
        }

        // Apply FIFO allocation
        $batchesAffected = $this->allocateFromBatches(
            $departmentStoreId,
            $command->recipeId,
            $command->quantity,
            $command->invoiceId
        );

        // Record to ledger
        $ledgerEntry = $this->ledgerRecorder->record([
            'recipe_id' => $command->recipeId,
            'department_id' => $command->departmentId,
            'quantity_before' => $currentQuantity,
            'quantity_after' => $currentQuantity - $command->quantity,
            'entry_type' => 'credit',
            'source_type' => $command->sourceType,
            'source_id' => $command->invoiceId ?? $command->orderId,
            'transaction_type' => $command->transactionType,
            'from_department_id' => $command->fromDepartmentId,
            'to_department_id' => $command->toDepartmentId,
            'unit_price' => $command->unitPrice,
            'notes' => $command->notes,
            'metadata' => array_merge($command->metadata ?? [], [
                'batches_affected' => $batchesAffected,
            ]),
        ]);

        return [
            'success' => true,
            'entry_type' => 'credit',
            'quantity_removed' => $command->quantity,
            'ledger_entry_id' => $ledgerEntry->id,
            'batches_affected' => $batchesAffected,
        ];
    }

    /**
     * Allocate quantity from batches using FIFO
     * Returns array of batch IDs affected
     * 
     * @param string $departmentStoreId
     * @param string $recipeId
     * @param float $quantityNeeded
     * @param string|null $specificInvoiceId For specific batch allocation
     * @return array
     */
    protected function allocateFromBatches(
        string $departmentStoreId,
        string $recipeId,
        float $quantityNeeded,
        ?string $specificInvoiceId = null
    ): array {
        $batchesAffected = [];

        // Get batches ordered by FIFO (earliest expiration first)
        $batches = RecipeQuantity::where('department_store_id', $departmentStoreId)
            ->where('recipe_id', $recipeId)
            ->when($specificInvoiceId, fn($query) => $query->where('invoice_id', $specificInvoiceId))
            ->where('remaining', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingQuantity = $quantityNeeded;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $quantityBefore = $batch->remaining;

            if ($batch->remaining >= $remainingQuantity) {
                // This batch has enough
                $batch->remaining -= $remainingQuantity;
                $batch->total_price = $batch->remaining * $batch->price;
                $batch->save();

                $batchesAffected[] = [
                    'batch_id' => $batch->id,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $batch->remaining,
                    'quantity_allocated' => $remainingQuantity,
                ];

                // Delete if empty
                if ($batch->remaining == 0) {
                    $batch->delete();
                }

                $remainingQuantity = 0;
            } else {
                // Take all from this batch and continue
                $allocatedFromBatch = $batch->remaining;
                $batch->remaining = 0;
                $batch->total_price = 0;
                $batch->save();
                $batch->delete();

                $batchesAffected[] = [
                    'batch_id' => $batch->id,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => 0,
                    'quantity_allocated' => $allocatedFromBatch,
                ];

                $remainingQuantity -= $allocatedFromBatch;
            }
        }

        if ($remainingQuantity > 0) {
            Log::warning('FIFO allocation could not fulfill entire quantity', [
                'department_store_id' => $departmentStoreId,
                'recipe_id' => $recipeId,
                'quantity_needed' => $quantityNeeded,
                'quantity_remaining' => $remainingQuantity,
                'batches_found' => $batches->count(),
            ]);
        }

        return $batchesAffected;
    }

    /**
     * Get or create department_store record
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return string Department store ID
     */
    protected function getOrCreateDepartmentStore(string $departmentId, string $recipeId): string
    {
        $store = DepartmentStore::where('department_id', $departmentId)
            ->where('recipe_id', $recipeId)
            ->first();

        if (!$store) {
            $store = DepartmentStore::create([
                'department_id' => $departmentId,
                'recipe_id' => $recipeId,
                'quantity' => 0,
                'price' => 0,
            ]);
        }

        return $store->id;
    }

    /**
     * Get current quantity for a recipe in department
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return float
     */
    protected function getCurrentQuantity(string $departmentId, string $recipeId): float
    {
        $departmentStoreId = $this->getOrCreateDepartmentStore($departmentId, $recipeId);

        return (float) RecipeQuantity::where('department_store_id', $departmentStoreId)
            ->where('recipe_id', $recipeId)
            ->sum('remaining');
    }

    /**
     * Update department_store aggregate after ledger operations
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return void
     */
    protected function updateDepartmentStore(string $departmentId, string $recipeId): void
    {
        $store = DepartmentStore::where('department_id', $departmentId)
            ->where('recipe_id', $recipeId)
            ->lockForUpdate()
            ->first();

        if (!$store) {
            return;
        }

        // Aggregate from recipe_quantities
        $quantities = RecipeQuantity::where('department_store_id', $store->id)
            ->where('recipe_id', $recipeId)
            ->where('remaining', '>', 0)
            ->get();

        $totalQuantity = $quantities->sum('remaining');
        $totalPrice = $quantities->sum('total_price');

        $store->update([
            'quantity' => $totalQuantity,
            'price' => $totalPrice,
        ]);

        Log::info('Department store updated from ledger', [
            'department_id' => $departmentId,
            'recipe_id' => $recipeId,
            'new_quantity' => $totalQuantity,
            'new_price' => $totalPrice,
        ]);
    }

    /**
     * Update department_store from a ledger entry
     * Can be called by observer
     * 
     * @param InventoryLedger $entry
     * @return void
     */
    public function updateDepartmentStoreFromEntry(InventoryLedger $entry): void
    {
        $this->updateDepartmentStore($entry->department_id, $entry->recipe_id);
    }

    /**
     * Verify sufficient inventory before processing removal
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @param float $quantity
     * @return bool
     */
    public function hasSufficientInventory(
        string $departmentId,
        string $recipeId,
        float $quantity
    ): bool {
        $available = $this->getCurrentQuantity($departmentId, $recipeId);
        return $available >= $quantity;
    }

    /**
     * Get available quantity for a recipe in department
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return float
     */
    public function getAvailableQuantity(string $departmentId, string $recipeId): float
    {
        return $this->getCurrentQuantity($departmentId, $recipeId);
    }

    /**
     * Preview FIFO allocation without applying
     * Useful for displaying what batches would be affected
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @param float $quantity
     * @return array
     */
    public function previewFifoAllocation(
        string $departmentId,
        string $recipeId,
        float $quantity
    ): array {
        $departmentStoreId = $this->getOrCreateDepartmentStore($departmentId, $recipeId);

        $batches = RecipeQuantity::where('department_store_id', $departmentStoreId)
            ->where('recipe_id', $recipeId)
            ->where('remaining', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->get();

        $preview = [];
        $remainingQuantity = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $allocated = min($batch->remaining, $remainingQuantity);
            
            $preview[] = [
                'batch_id' => $batch->id,
                'invoice_id' => $batch->invoice_id,
                'expire_date' => $batch->expire_date,
                'price' => $batch->price,
                'current_remaining' => $batch->remaining,
                'will_allocate' => $allocated,
                'will_remain' => $batch->remaining - $allocated,
            ];

            $remainingQuantity -= $allocated;
        }

        return [
            'can_fulfill' => $remainingQuantity == 0,
            'quantity_requested' => $quantity,
            'quantity_available' => $quantity - $remainingQuantity,
            'batches_to_affect' => $preview,
        ];
    }
}

