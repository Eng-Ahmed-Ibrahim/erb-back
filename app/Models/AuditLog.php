<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'audits';

    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model instance
     */
    public function getModelAttribute()
    {
        $modelClass = $this->getModelClass();
        if ($modelClass && class_exists($modelClass)) {
            return $modelClass::find($this->model_id);
        }
        return null;
    }

    /**
     * Get the model class name based on model_type
     */
    protected function getModelClass()
    {
        $modelMap = [
            'invoice' => \App\Models\Invoice::class,
            'order' => \App\Models\Order::class,
            'recipe' => \App\Models\Recipe::class,
            'product' => \App\Models\Product::class,
            'supplier' => \App\Models\Supplier::class,
            'department' => \App\Models\Department::class,
            'user' => \App\Models\User::class,
            'client' => \App\Models\Client::class,
            'category' => \App\Models\Category::class,
            'sub_category' => \App\Models\SubCategory::class,
            'unit' => \App\Models\Unit::class,
            'payment_method' => \App\Models\PaymentMethod::class,
            'role' => \App\Models\Role::class,
            'permission' => \App\Models\Permission::class,
        ];

        return $modelMap[$this->model_type] ?? null;
    }

    /**
     * Log an action for any model
     */
    public static function logAction($modelType, $modelId, $action, $fieldName = null, $oldValue = null, $newValue = null, $details = null)
    {
        return self::create([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'user_id' => auth()->id(),
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log model creation
     */
    public static function logCreation($modelType, $modelId, $modelData = null, $details = null)
    {
        return self::logAction(
            $modelType,
            $modelId,
            'created',
            null,
            null,
            $modelData,
            $details
        );
    }

    /**
     * Log model update
     */
    public static function logUpdate($modelType, $modelId, $fieldName, $oldValue, $newValue, $details = null)
    {
        return self::logAction(
            $modelType,
            $modelId,
            'updated',
            $fieldName,
            $oldValue,
            $newValue,
            $details
        );
    }

    /**
     * Log model deletion
     */
    public static function logDeletion($modelType, $modelId, $modelData = null, $details = null)
    {
        return self::logAction(
            $modelType,
            $modelId,
            'deleted',
            null,
            $modelData,
            null,
            $details
        );
    }

    /**
     * Log custom action
     */
    public static function logCustomAction($modelType, $modelId, $action, $details = null)
    {
        return self::logAction(
            $modelType,
            $modelId,
            $action,
            null,
            null,
            null,
            $details
        );
    }
}
