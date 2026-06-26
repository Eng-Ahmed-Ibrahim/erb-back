<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Price extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'price',
        'default',
        'product_id',
        'client_type_id',
        'client_id',
        'service',
        'profit',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function client_type()
    {
        return $this->belongsTo(ClientType::class, 'client_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
