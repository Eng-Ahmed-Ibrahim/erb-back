<?php

namespace App\Models;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Waiter;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DepartmentInventoryReview extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'department_id',
        'cashier_id',
        'invoice_id',
        'waiter_id',
        'discrepancy_note',
        'total_missing_quantity',
        'estimated_loss_amount',
        'reviewed_by',
        'reviewed_at',
        'status',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function waiter()
    {
        return $this->belongsTo(Waiter::class, 'waiter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
