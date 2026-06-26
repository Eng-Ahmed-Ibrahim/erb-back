<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class InvoiceRecipe extends Model implements Auditable
{
    use HasFactory , \OwenIt\Auditing\Auditable;

    protected $table = 'invoice_recipe';

    protected $fillable = [
        'invoice_id',
        'recipe_id',
        'quantity',
        'price',
        'total_price',
    ];
}
