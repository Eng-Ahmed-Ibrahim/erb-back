<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DeletedOrderProduct extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'deleted_order_id',
        'order_product_id',
        'product_id',
        'quantity',
        'price',
        'product_type',
        'deletion_note',
        'deleted_by',
        'reviewed_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(DeletedOrder::class);
    }
}
