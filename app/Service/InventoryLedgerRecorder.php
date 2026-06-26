<?php

namespace App\Service;

use App\Models\InventoryLedger;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Department;
use App\Models\Recipe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventoryLedgerRecorder
{
    /**
     * Record a generic inventory change
     * 
     * @param array $data
     * @return InventoryLedger
     */
    public function record(array $data): InventoryLedger
    {
        // Calculate delta
        $quantityBefore = $data['quantity_before'] ?? 0;
        $quantityAfter = $data['quantity_after'] ?? 0;
        $delta = abs($quantityAfter - $quantityBefore);
        
        // Determine entry type if not provided
        if (!isset($data['entry_type'])) {
            $data['entry_type'] = $quantityAfter > $quantityBefore ? 'debit' : 'credit';
        }
        
        $entry = InventoryLedger::create([
            'recipe_id' => $data['recipe_id'],
            'department_id' => $data['department_id'],
            'entry_type' => $data['entry_type'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'quantity_delta' => $delta,
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'] ?? null,
            'transaction_type' => $data['transaction_type'] ?? null,
            'from_department_id' => $data['from_department_id'] ?? null,
            'to_department_id' => $data['to_department_id'] ?? null,
            'unit_price' => $data['unit_price'] ?? 0,
            'total_value' => $delta * ($data['unit_price'] ?? 0),
            'recipe_quantity_id' => $data['recipe_quantity_id'] ?? null,
            'expire_date' => $data['expire_date'] ?? null,
            'created_by' => $data['created_by'] ?? auth('api')->id(),
            'transaction_date' => $data['transaction_date'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
        
        Log::info('Inventory ledger entry recorded', [
            'ledger_id' => $entry->id,
            'recipe_id' => $entry->recipe_id,
            'department_id' => $entry->department_id,
            'entry_type' => $entry->entry_type,
            'delta' => $delta,
            'source' => "{$entry->source_type}:{$entry->source_id}",
            'transaction_type' => $entry->transaction_type,
        ]);
        
        return $entry;
    }

    /**
     * Record invoice movement (handles both source and destination)
     * 
     * @param Invoice $invoice
     * @param Recipe $recipe
     * @param string $departmentId
     * @param float $quantityBefore
     * @param float $quantityAfter
     * @param bool $isSourceDepartment
     * @return InventoryLedger
     */
    public function recordInvoiceMovement(
        Invoice $invoice,
        $recipe,
        string $departmentId,
        float $quantityBefore,
        float $quantityAfter,
        bool $isSourceDepartment = false,
        ?string $recipeQuantityId = null
    ): InventoryLedger {
        return $this->record([
            'recipe_id' => is_object($recipe) ? $recipe->id : $recipe,
            'department_id' => $departmentId,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'transaction_type' => $invoice->type,
            'from_department_id' => $invoice->from,
            'to_department_id' => $invoice->to,
            'unit_price' => is_object($recipe) ? ($recipe->pivot->price ?? 0) : 0,
            'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
            'recipe_quantity_id' => $recipeQuantityId,
            'notes' => $isSourceDepartment 
                ? "Invoice {$invoice->code} - Transferred out to department"
                : "Invoice {$invoice->code} - Received from " . ($invoice->from ? "department" : "supplier"),
        ]);
    }

    /**
     * Record order consumption
     * 
     * @param Order $order
     * @param mixed $recipe
     * @param string $departmentId
     * @param float $quantityBefore
     * @param float $quantityAfter
     * @param float $unitPrice
     * @param string|null $recipeQuantityId
     * @return InventoryLedger
     */
    public function recordOrderConsumption(
        Order $order,
        $recipe,
        string $departmentId,
        float $quantityBefore,
        float $quantityAfter,
        float $unitPrice = 0,
        ?string $recipeQuantityId = null
    ): InventoryLedger {
        return $this->record([
            'recipe_id' => is_object($recipe) ? $recipe->id : $recipe,
            'department_id' => $departmentId,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'entry_type' => 'credit', // Always removing from inventory
            'source_type' => 'order',
            'source_id' => $order->id,
            'transaction_type' => 'consumption',
            'unit_price' => $unitPrice,
            'transaction_date' => $order->created_at,
            'recipe_quantity_id' => $recipeQuantityId,
            'notes' => "Order {$order->code} - Consumed by customer",
        ]);
    }

    /**
     * Record manual adjustment
     * 
     * @param string $recipeId
     * @param string $departmentId
     * @param float $quantityBefore
     * @param float $quantityAfter
     * @param string $reason
     * @return InventoryLedger
     */
    public function recordAdjustment(
        string $recipeId,
        string $departmentId,
        float $quantityBefore,
        float $quantityAfter,
        string $reason
    ): InventoryLedger {
        return $this->record([
            'recipe_id' => $recipeId,
            'department_id' => $departmentId,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'source_type' => 'adjustment',
            'transaction_type' => 'adjustment',
            'notes' => $reason,
        ]);
    }

    /**
     * Record opening balance (for initial setup or migration)
     * 
     * @param string $recipeId
     * @param string $departmentId
     * @param float $quantity
     * @param float $unitPrice
     * @return InventoryLedger
     */
    public function recordOpeningBalance(
        string $recipeId,
        string $departmentId,
        float $quantity,
        float $unitPrice = 0
    ): InventoryLedger {
        return $this->record([
            'recipe_id' => $recipeId,
            'department_id' => $departmentId,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'entry_type' => 'debit',
            'source_type' => 'opening_balance',
            'transaction_type' => 'opening_balance',
            'unit_price' => $unitPrice,
            'notes' => 'Opening balance - System initialization',
        ]);
    }

    /**
     * Get current balance from ledger for a recipe in department
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return float
     */
    public function getLedgerBalance(string $departmentId, string $recipeId): float
    {
        $lastEntry = InventoryLedger::forDepartmentAndRecipe($departmentId, $recipeId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastEntry ? (float) $lastEntry->quantity_after : 0;
    }

    /**
     * Get movement summary for a recipe in department within date range
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getMovementSummary(
        string $departmentId,
        string $recipeId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = InventoryLedger::forDepartmentAndRecipe($departmentId, $recipeId);

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $entries = $query->orderBy('transaction_date')->get();

        $totalDebits = $entries->where('entry_type', 'debit')->sum('quantity_delta');
        $totalCredits = $entries->where('entry_type', 'credit')->sum('quantity_delta');

        return [
            'department_id' => $departmentId,
            'recipe_id' => $recipeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_in' => $totalDebits,
            'total_out' => $totalCredits,
            'net_movement' => $totalDebits - $totalCredits,
            'entry_count' => $entries->count(),
            'entries' => $entries,
        ];
    }

    /**
     * Get ledger entries for a specific source document
     * 
     * @param string $sourceType
     * @param string $sourceId
     * @return \Illuminate\Support\Collection
     */
    public function getEntriesForSource(string $sourceType, string $sourceId)
    {
        return InventoryLedger::bySource($sourceType, $sourceId)
            ->with(['recipe', 'department', 'createdBy'])
            ->orderBy('created_at')
            ->get();
    }
}

