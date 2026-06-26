<?php

namespace App\Service;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Log model creation
     */
    public static function logCreation($modelType, $modelId, $modelData = null, $details = null)
    {
        return AuditLog::logCreation($modelType, $modelId, $modelData, $details);
    }

    /**
     * Log model update
     */
    public static function logUpdate($modelType, $modelId, $oldData, $newData, $details = null)
    {
        $changes = array_diff_assoc($newData, $oldData);
        
        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at', 'created_at'])) {
                continue;
            }
            
            AuditLog::logUpdate(
                $modelType,
                $modelId,
                $field,
                $oldData[$field] ?? null,
                $newValue,
                $details
            );
        }
    }

    /**
     * Log model deletion
     */
    public static function logDeletion($modelType, $modelId, $modelData = null, $details = null)
    {
        return AuditLog::logDeletion($modelType, $modelId, $modelData, $details);
    }

    /**
     * Log custom action
     */
    public static function logCustomAction($modelType, $modelId, $action, $details = null)
    {
        return AuditLog::logCustomAction($modelType, $modelId, $action, $details);
    }

    /**
     * Log quantity change
     */
    public static function logQuantityChange($modelType, $modelId, $fieldName, $oldQuantity, $newQuantity, $details = null)
    {
        return AuditLog::logAction(
            $modelType,
            $modelId,
            'quantity_changed',
            $fieldName,
            $oldQuantity,
            $newQuantity,
            $details
        );
    }

    /**
     * Log price change
     */
    public static function logPriceChange($modelType, $modelId, $fieldName, $oldPrice, $newPrice, $details = null)
    {
        return AuditLog::logAction(
            $modelType,
            $modelId,
            'price_changed',
            $fieldName,
            $oldPrice,
            $newPrice,
            $details
        );
    }

    /**
     * Log status change
     */
    public static function logStatusChange($modelType, $modelId, $oldStatus, $newStatus, $details = null)
    {
        return AuditLog::logAction(
            $modelType,
            $modelId,
            'status_changed',
            'status',
            $oldStatus,
            $newStatus,
            $details
        );
    }

    /**
     * Log operation result
     */
    public static function logOperationResult($modelType, $modelId, $operation, $result, $details = null)
    {
        $status = $result['status'] ?? false;
        $message = $result['message'] ?? 'Unknown result';
        
        return AuditLog::logAction(
            $modelType,
            $modelId,
            $operation . '_result',
            'operation_status',
            null,
            [
                'status' => $status,
                'message' => $message,
                'operation' => $operation
            ],
            array_merge($details ?? [], ['full_result' => $result])
        );
    }

    /**
     * Log exception
     */
    public static function logException($modelType, $modelId, $exception, $details = null)
    {
        return AuditLog::logAction(
            $modelType,
            $modelId,
            'exception_occurred',
            'error',
            null,
            [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stack_trace' => $exception->getTraceAsString()
            ],
            $details
        );
    }
}
