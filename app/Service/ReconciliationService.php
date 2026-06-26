<?php

namespace App\Service;

use App\Models\Department;
use App\Models\DepartmentStore;
use App\Models\InventoryLedger;
use App\Models\Recipe;
use App\Models\RecipeQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReconciliationService
{
    /**
     * Reconcile a specific recipe in a department
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @param Carbon|null $asOfDate
     * @return array
     */
    public function reconcileRecipe(
        string $departmentId,
        string $recipeId,
        ?Carbon $asOfDate = null
    ): array {
        $asOfDate = $asOfDate ?? now();

        // Get ledger balance
        $ledgerBalance = $this->getLedgerBalance($departmentId, $recipeId, $asOfDate);

        // Get actual balance from recipe_quantities
        $actualBalance = $this->getActualBalance($departmentId, $recipeId);

        // Get department_store balance
        $storeBalance = $this->getStoreBalance($departmentId, $recipeId);

        // Calculate discrepancies
        $ledgerVsActual = $actualBalance - $ledgerBalance;
        $storeVsActual = $actualBalance - $storeBalance;

        $isBalanced = abs($ledgerVsActual) < 0.001 && abs($storeVsActual) < 0.001;

        $result = [
            'department_id' => $departmentId,
            'recipe_id' => $recipeId,
            'as_of_date' => $asOfDate->toDateTimeString(),
            'ledger_balance' => round($ledgerBalance, 3),
            'actual_balance' => round($actualBalance, 3),
            'store_balance' => round($storeBalance, 3),
            'ledger_vs_actual_diff' => round($ledgerVsActual, 3),
            'store_vs_actual_diff' => round($storeVsActual, 3),
            'is_balanced' => $isBalanced,
            'status' => $isBalanced ? 'OK' : 'DISCREPANCY',
        ];

        if (!$isBalanced) {
            Log::warning('Inventory discrepancy detected', $result);
        }

        return $result;
    }

    /**
     * Reconcile all recipes in a department
     * 
     * @param string $departmentId
     * @param Carbon|null $asOfDate
     * @return array
     */
    public function reconcileDepartment(
        string $departmentId,
        ?Carbon $asOfDate = null
    ): array {
        $asOfDate = $asOfDate ?? now();

        // Get all recipes that have ever been in this department
        $recipeIds = DepartmentStore::where('department_id', $departmentId)
            ->pluck('recipe_id');

        $results = [];
        $totalDiscrepancies = 0;
        $totalRecipes = 0;

        foreach ($recipeIds as $recipeId) {
            $reconciliation = $this->reconcileRecipe($departmentId, $recipeId, $asOfDate);
            $results[] = $reconciliation;
            $totalRecipes++;

            if (!$reconciliation['is_balanced']) {
                $totalDiscrepancies++;
            }
        }

        return [
            'department_id' => $departmentId,
            'as_of_date' => $asOfDate->toDateTimeString(),
            'total_recipes' => $totalRecipes,
            'total_discrepancies' => $totalDiscrepancies,
            'accuracy_rate' => $totalRecipes > 0 
                ? round((($totalRecipes - $totalDiscrepancies) / $totalRecipes) * 100, 2)
                : 100,
            'status' => $totalDiscrepancies === 0 ? 'BALANCED' : 'HAS_DISCREPANCIES',
            'details' => $results,
        ];
    }

    /**
     * Reconcile entire system
     * 
     * @param Carbon|null $asOfDate
     * @return array
     */
    public function reconcileSystem(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();

        $departments = Department::all();
        $results = [];
        $totalDiscrepancies = 0;
        $totalRecipes = 0;

        foreach ($departments as $department) {
            $deptReconciliation = $this->reconcileDepartment($department->id, $asOfDate);
            $results[] = [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'summary' => [
                    'total_recipes' => $deptReconciliation['total_recipes'],
                    'discrepancies' => $deptReconciliation['total_discrepancies'],
                    'status' => $deptReconciliation['status'],
                ],
            ];

            $totalRecipes += $deptReconciliation['total_recipes'];
            $totalDiscrepancies += $deptReconciliation['total_discrepancies'];
        }

        return [
            'reconciliation_date' => $asOfDate->toDateTimeString(),
            'total_departments' => $departments->count(),
            'total_recipes_checked' => $totalRecipes,
            'total_discrepancies' => $totalDiscrepancies,
            'system_accuracy_rate' => $totalRecipes > 0
                ? round((($totalRecipes - $totalDiscrepancies) / $totalRecipes) * 100, 2)
                : 100,
            'system_status' => $totalDiscrepancies === 0 ? 'BALANCED' : 'HAS_DISCREPANCIES',
            'departments' => $results,
        ];
    }

    /**
     * Get ledger balance (from latest ledger entry)
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @param Carbon $asOfDate
     * @return float
     */
    protected function getLedgerBalance(
        string $departmentId,
        string $recipeId,
        Carbon $asOfDate
    ): float {
        $lastEntry = InventoryLedger::forDepartmentAndRecipe($departmentId, $recipeId)
            ->where('created_at', '<=', $asOfDate)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastEntry ? (float) $lastEntry->quantity_after : 0;
    }

    /**
     * Get actual balance from recipe_quantities table
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return float
     */
    protected function getActualBalance(string $departmentId, string $recipeId): float
    {
        $departmentStore = DepartmentStore::where('department_id', $departmentId)
            ->where('recipe_id', $recipeId)
            ->first();

        if (!$departmentStore) {
            return 0;
        }

        return (float) RecipeQuantity::where('department_store_id', $departmentStore->id)
            ->where('recipe_id', $recipeId)
            ->sum('remaining');
    }

    /**
     * Get store balance from department_store table
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @return float
     */
    protected function getStoreBalance(string $departmentId, string $recipeId): float
    {
        $departmentStore = DepartmentStore::where('department_id', $departmentId)
            ->where('recipe_id', $recipeId)
            ->first();

        return $departmentStore ? (float) $departmentStore->quantity : 0;
    }

    /**
     * Find all discrepancies in the system
     * 
     * @return array
     */
    public function findAllDiscrepancies(): array
    {
        $discrepancies = [];

        $departmentStores = DepartmentStore::all();

        foreach ($departmentStores as $store) {
            $ledgerBalance = $this->getLedgerBalance($store->department_id, $store->recipe_id, now());
            $actualBalance = $this->getActualBalance($store->department_id, $store->recipe_id);
            $storeBalance = (float) $store->quantity;

            $ledgerDiff = abs($actualBalance - $ledgerBalance);
            $storeDiff = abs($actualBalance - $storeBalance);

            if ($ledgerDiff > 0.001 || $storeDiff > 0.001) {
                $discrepancies[] = [
                    'department_id' => $store->department_id,
                    'recipe_id' => $store->recipe_id,
                    'ledger_balance' => round($ledgerBalance, 3),
                    'actual_balance' => round($actualBalance, 3),
                    'store_balance' => round($storeBalance, 3),
                    'ledger_difference' => round($ledgerDiff, 3),
                    'store_difference' => round($storeDiff, 3),
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Generate detailed reconciliation report
     * 
     * @param string $departmentId
     * @param string $recipeId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function generateDetailedReport(
        string $departmentId,
        string $recipeId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        // Get all ledger entries in date range
        $entries = InventoryLedger::forDepartmentAndRecipe($departmentId, $recipeId)
            ->betweenDates($startDate, $endDate)
            ->orderBy('transaction_date')
            ->with(['createdBy', 'invoice', 'order'])
            ->get();

        // Calculate totals
        $totalDebits = $entries->where('entry_type', 'debit')->sum('quantity_delta');
        $totalCredits = $entries->where('entry_type', 'credit')->sum('quantity_delta');
        $netMovement = $totalDebits - $totalCredits;

        // Group by transaction type
        $byTransactionType = $entries->groupBy('transaction_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_in' => $group->where('entry_type', 'debit')->sum('quantity_delta'),
                'total_out' => $group->where('entry_type', 'credit')->sum('quantity_delta'),
            ];
        });

        // Get current reconciliation
        $reconciliation = $this->reconcileRecipe($departmentId, $recipeId);

        return [
            'department_id' => $departmentId,
            'recipe_id' => $recipeId,
            'period' => [
                'start' => $startDate->toDateTimeString(),
                'end' => $endDate->toDateTimeString(),
            ],
            'summary' => [
                'total_entries' => $entries->count(),
                'total_in' => round($totalDebits, 3),
                'total_out' => round($totalCredits, 3),
                'net_movement' => round($netMovement, 3),
            ],
            'by_transaction_type' => $byTransactionType,
            'current_reconciliation' => $reconciliation,
            'entries' => $entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'date' => $entry->transaction_date,
                    'type' => $entry->transaction_type,
                    'entry_type' => $entry->entry_type,
                    'quantity_before' => $entry->quantity_before,
                    'quantity_after' => $entry->quantity_after,
                    'quantity_delta' => $entry->quantity_delta,
                    'source' => [
                        'type' => $entry->source_type,
                        'id' => $entry->source_id,
                        'code' => $entry->invoice?->code ?? $entry->order?->code ?? null,
                    ],
                    'created_by' => $entry->createdBy?->name,
                    'notes' => $entry->notes,
                ];
            }),
        ];
    }
}

