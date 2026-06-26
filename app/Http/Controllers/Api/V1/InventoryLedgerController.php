<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Service\InventoryLedgerRecorder;
use App\Service\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class InventoryLedgerController extends Controller
{
    protected $ledgerRecorder;
    protected $reconciliationService;

    public function __construct(
        InventoryLedgerRecorder $ledgerRecorder,
        ReconciliationService $reconciliationService
    ) {
        $this->ledgerRecorder = $ledgerRecorder;
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * Get ledger entries for a recipe
     */
    public function getRecipeLedger(Request $request, $recipeId)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $report = $this->ledgerRecorder->getMovementSummary(
            $request->department_id ?? '',
            $recipeId,
            $request->start_date,
            $request->end_date
        );

        return responder()->success($report)->respond(Response::HTTP_OK);
    }

    /**
     * Get ledger entries for a source document (invoice or order)
     */
    public function getSourceLedger(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:invoice,order,adjustment',
            'source_id' => 'required|string',
        ]);

        $entries = $this->ledgerRecorder->getEntriesForSource(
            $request->source_type,
            $request->source_id
        );

        return responder()->success($entries)->respond(Response::HTTP_OK);
    }

    /**
     * Get current ledger balance for a recipe in department
     */
    public function getLedgerBalance(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        $balance = $this->ledgerRecorder->getLedgerBalance(
            $request->department_id,
            $request->recipe_id
        );

        return responder()->success([
            'department_id' => $request->department_id,
            'recipe_id' => $request->recipe_id,
            'balance' => $balance,
        ])->respond(Response::HTTP_OK);
    }

    /**
     * Reconcile a specific recipe in a department
     */
    public function reconcileRecipe(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'recipe_id' => 'required|exists:recipes,id',
            'as_of_date' => 'nullable|date',
        ]);

        $result = $this->reconciliationService->reconcileRecipe(
            $request->department_id,
            $request->recipe_id,
            $request->as_of_date ? Carbon::parse($request->as_of_date) : null
        );

        return responder()->success($result)->respond(Response::HTTP_OK);
    }

    /**
     * Reconcile all recipes in a department
     */
    public function reconcileDepartment(Request $request, $departmentId)
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $result = $this->reconciliationService->reconcileDepartment(
            $departmentId,
            $request->as_of_date ? Carbon::parse($request->as_of_date) : null
        );

        return responder()->success($result)->respond(Response::HTTP_OK);
    }

    /**
     * Reconcile entire system
     */
    public function reconcileSystem(Request $request)
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $result = $this->reconciliationService->reconcileSystem(
            $request->as_of_date ? Carbon::parse($request->as_of_date) : null
        );

        return responder()->success($result)->respond(Response::HTTP_OK);
    }

    /**
     * Find all discrepancies in the system
     */
    public function findDiscrepancies()
    {
        $discrepancies = $this->reconciliationService->findAllDiscrepancies();

        return responder()->success([
            'total_discrepancies' => count($discrepancies),
            'discrepancies' => $discrepancies,
        ])->respond(Response::HTTP_OK);
    }

    /**
     * Generate detailed reconciliation report
     */
    public function detailedReport(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'recipe_id' => 'required|exists:recipes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $report = $this->reconciliationService->generateDetailedReport(
            $request->department_id,
            $request->recipe_id,
            $request->start_date ? Carbon::parse($request->start_date) : null,
            $request->end_date ? Carbon::parse($request->end_date) : null
        );

        return responder()->success($report)->respond(Response::HTTP_OK);
    }

    /**
     * Health check endpoint - verify system integrity
     */
    public function healthCheck()
    {
        $systemReconciliation = $this->reconciliationService->reconcileSystem();
        $discrepancies = $this->reconciliationService->findAllDiscrepancies();

        return responder()->success([
            'timestamp' => now()->toDateTimeString(),
            'system_status' => $systemReconciliation['system_status'],
            'accuracy_rate' => $systemReconciliation['system_accuracy_rate'],
            'total_discrepancies' => count($discrepancies),
            'summary' => [
                'departments_checked' => $systemReconciliation['total_departments'],
                'recipes_checked' => $systemReconciliation['total_recipes_checked'],
                'issues_found' => $systemReconciliation['total_discrepancies'],
            ],
            'health_status' => count($discrepancies) === 0 ? 'HEALTHY' : 'NEEDS_ATTENTION',
        ])->respond(Response::HTTP_OK);
    }
}

