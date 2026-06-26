<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RecipeQuantity extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'order',
        'quantity',
        'remaining',
        'price',
        'expire_date',
        'department_store_id',
        'invoice_id',
        'recipe_id',
        'total_price',
    ];

    public function department_store()
    {
        return $this->belongsTo(DepartmentStore::class, 'department_store_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }
}
