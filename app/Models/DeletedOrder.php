<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DeletedOrder extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $table = 'deleted_orders';

    protected $fillable = [
        'status',
        'code',
        'order_date',
        'order_id',
        'department_id',
        'client_id',
        'client_type_id',
        'comment',
        'table_number',
        'payment_method_id',
        'price',
        'tax',
        'discount',
        'total_price',
        'user_id',
        'waiter_id',
        'deleted_by',
        'reviewed_by',
        'deletion_note',
        'discount_reason_id',
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
        return $this->hasMany(DeletedOrderProduct::class);
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function getTotalPriceAttribute($client_type_id = null, $c = null)
    // {
    //     return $this->products->sum(function ($product) use ($client_type_id, $c) {
    //         $c_type = $client_type_id;

    //         $price = Price::where([
    //             'product_id'=>$product->product?->id,
    //             'client_type_id'=>$c_type,
    //             'client_id'=>$c
    //         ])->latest()->first();
    //         // $price = $product->product?->prices->where(
    //         //     function ($query) use ($c_type, $c) {
    //         //         $query->where('client_id', $c);
    //         //     }
    //         // )->first();
    //         if (!$price) {
    //             $price = $product->product?->prices->where('default', 1)->first();
    //         }
    //         $p = $price ? $price->price : 0;
    //         $q =  $product->quantity;
    //         $i = $price ? ((($p+$price->profit) * $price->service) / 100 ) : 0;
    //         return ( $p   + ($price ? $price->profit : 0) + $i)*$q ;
    //     });
    // }
}
