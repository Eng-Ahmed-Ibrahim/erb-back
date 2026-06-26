<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'inventory_ledger';

    protected $fillable = [
        'recipe_id',
        'department_id',
        'entry_type',
        'quantity_before',
        'quantity_after',
        'quantity_delta',
        'source_type',
        'source_id',
        'transaction_type',
        'from_department_id',
        'to_department_id',
        'unit_price',
        'total_value',
        'recipe_quantity_id',
        'expire_date',
        'created_by',
        'transaction_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'expire_date' => 'date',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'quantity_delta' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'total_value' => 'decimal:3',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'source_id')
            ->where('source_type', 'invoice');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'source_id')
            ->where('source_type', 'order');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function recipeQuantity()
    {
        return $this->belongsTo(RecipeQuantity::class, 'recipe_quantity_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes for easy querying
     */
    public function scopeForRecipe($query, $recipeId)
    {
        return $query->where('recipe_id', $recipeId);
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeForDepartmentAndRecipe($query, $departmentId, $recipeId)
    {
        return $query->where('department_id', $departmentId)
            ->where('recipe_id', $recipeId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeDebits($query)
    {
        return $query->where('entry_type', 'debit');
    }

    public function scopeCredits($query)
    {
        return $query->where('entry_type', 'credit');
    }

    public function scopeBySource($query, $sourceType, $sourceId)
    {
        return $query->where('source_type', $sourceType)
            ->where('source_id', $sourceId);
    }

    public function scopeByTransactionType($query, $transactionType)
    {
        return $query->where('transaction_type', $transactionType);
    }

    /**
     * Helper methods
     */
    public function isDebit(): bool
    {
        return $this->entry_type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->entry_type === 'credit';
    }

    public function getSignedQuantityAttribute(): float
    {
        return $this->isDebit() ? $this->quantity_delta : -$this->quantity_delta;
    }
}

