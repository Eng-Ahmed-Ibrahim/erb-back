<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class Department extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    // const Maintainance

    protected $fillable = [
        'name',
        'image',
        'code',
        'phone',
        'type',
        'linked_department',
        'has_tax',
        'has_instant_order_closing',
        'is_orders_visible',
        'has_instructions', 
        'instructions',
        'section_id',
    ];

    const EXTERNAL_ORDERS_DEPARTMENT = '01jc5z6v97vmshra4kzmhpqave';
    const STOCK_DEPARTMENT = '01hy3km07mf7fafqn2j6388d1t';


    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function fromInvoices()
    {
        return $this->hasMany(Invoice::class, 'from');
    }

    public function toInvoices()
    {
        return $this->hasMany(Invoice::class, 'to');
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'department_store')
            ->withPivot(['id', 'quantity', 'price', 'over_quantity', 'actual_quantity', 'under_quantity'])
            ->using(new class extends Pivot
            {
                use HasUlids;
            })
            ->withTimestamps();
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity', 'id')
            ->orderBy('id', 'desc')
            ->using(new class extends Pivot
            {
                use HasUlids;
            });
    }

    public function balances()
    {
        return $this->hasMany(Balance::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'department_id');
    }
    public function clientTypes(){
        return $this->belongsToMany(ClientType::class, 'client_type_department', 'department_id', 'client_type_id');
    }
}
