<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class InventoryBlindCount extends Model implements Auditable
{
    use HasFactory;
    use HasUlids;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'department_id',
        'cashier_id',
        'waiter_old_id',
        'waiter_new_id',
        'submitted_at',
        'notes',
        'items_count',
        'total_under_quantity',
        'total_over_quantity',
        'total_fine_amount',
        'status',
        'pdf_path',
        'approved_by',
        'approved_at',
        'invoice_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_under_quantity' => 'decimal:3',
        'total_over_quantity' => 'decimal:3',
        'total_fine_amount' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function waiterOld()
    {
        return $this->belongsTo(Waiter::class, 'waiter_old_id');
    }

    public function waiterNew()
    {
        return $this->belongsTo(Waiter::class, 'waiter_new_id');
    }

    public function items()
    {
        return $this->hasMany(InventoryBlindCountItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}



