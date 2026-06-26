<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;
class Order extends Model implements Auditable
{
    use HasFactory, HasUlids, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'status',
        'code',
        'order_date',
        'department_id',
        'client_id',
        'client_type_id',
        'comment',
        'deleviery_type',
        'table_number',
        'to_kitchen',
        'payment_method_id',
        'discount_reason_id',
        'price',
        'tax',
        'discount',
        'total_price',
        'user_id',
        // 'created_by',
        'waiter_id',
        'payment_method',
        'is_printed',
        'closed_at',
        'created_at',

    ];

    const KITCHEN_DEPARTMENTS = ['3d1e1d26-91ff-40b8-9b2c-139aa79430e9', '01j45gtesjz0mm3qf0sz6bzvn9'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function clientType()
    {
        return $this->belongsTo(ClientType::class, 'client_type_id');
    }

    public function discounReason()
    {
        return $this->belongsTo(DiscountReason::class, 'discount_reason_id');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function payables()
    {
        return $this->hasMany(OrderPayable::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function waiter()
    {
        return $this->belongsTo(Waiter::class, 'waiter_id');
    }

    /*************  ✨ Codeium Command ⭐  *************/
    /******  86f9ad2b-db0d-494c-8e75-f4f261540bbb  *******/
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function hasOrderToday($clientTypeId, $clientId = null)
    {
        // If no client ID, don't apply the restriction
        if (!$clientId) {
            return false;
        }

        $today =  now()->setTime(4, 0, 0);

        return static::where('client_type_id', $clientTypeId)
            ->where('client_id', $clientId)
            // ->where("status","!=","failed_print")
            ->where('created_at', '>=', now()->setTime(4, 0, 0))
            ->exists();
    }
    
    // protected static function booted()
    // {
    //     static::addGlobalScope('excludeFailedPrint', function (Builder $builder) {
    //         $builder->where('status', '!=', 'failed_print');
    //     });
    // }
}
