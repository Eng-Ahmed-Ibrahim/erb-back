<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class InventoryBlindCountItem extends Model implements Auditable
{
    use HasFactory;
    use HasUlids;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'inventory_blind_count_id',
        'department_store_id',
        'recipe_id',
        'system_quantity',
        'actual_quantity',
        'variance_quantity',
        'variance_type',
        'unit_cost',
        'fine_amount',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'fine_amount' => 'decimal:2',
    ];

    public function blindCount()
    {
        return $this->belongsTo(InventoryBlindCount::class, 'inventory_blind_count_id');
    }

    public function departmentStore()
    {
        return $this->belongsTo(DepartmentStore::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}



