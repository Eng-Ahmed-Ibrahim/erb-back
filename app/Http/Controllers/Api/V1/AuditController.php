<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuditController extends Controller
{
    /**
     * Get all audit logs with simple filtering
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by model ID
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in all fields
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('model_id', 'like', "%{$search}%")
                    ->orWhere('field_name', 'like', "%{$search}%");
            });
        }

        // Sort by latest first
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 50);
        $auditLogs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $auditLogs->items(),
            'pagination' => [
                'current_page' => $auditLogs->currentPage(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $auditLogs->perPage(),
                'total' => $auditLogs->total(),
            ]
        ]);
    }

    /**
     * Get audit log details
     */
    public function show($id)
    {
        $auditLog = AuditLog::with('user')->find($id);

        if (!$auditLog) {
            return response()->json([
                'success' => false,
                'message' => 'سجل التدقيق غير موجود'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $auditLog
        ]);
    }

    /**
     * Get audit logs for a specific model
     */
    public function getModelAudit($modelType, $modelId)
    {
        $auditLogs = AuditLog::with('user')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $auditLogs
        ]);
    }

    /**
     * Get available model types for filtering
     */
    public function getModelTypes()
    {
        $modelTypes = AuditLog::distinct()
            ->pluck('model_type')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $modelTypes
        ]);
    }

    /**
     * Get available actions for filtering
     */
    public function getActions()
    {
        $actions = AuditLog::distinct()
            ->pluck('action')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $actions
        ]);
    }
}
