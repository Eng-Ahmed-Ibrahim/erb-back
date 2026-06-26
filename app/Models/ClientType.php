<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class ClientType extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'discount',
        'monthly_discount_limit',
        'tax',
        'new_client',
        'one_order_per_day',
        'preferred_currency',
        'preferred_currency_name_ar',
        'currency_divisor',
    ];

    /**
     * Not mass-assigned via API; set directly in DB when needed.
     */
    protected $casts = [
        'use_cost_basis' => 'boolean',
    ];

    const DEPARTMENT_MANAGER_ID = '25621e6e-dbb2-4524-91ab-2acf699e9e97';

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class)
            ->using(new class extends Pivot
            {
                use HasUlids;
            })
            ->withTimestamps();
    }

    public function orders()
    {
        $this->hasMany(Order::class, 'client_type_id');
    }
}
