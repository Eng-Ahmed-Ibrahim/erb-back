<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RecipeBalance extends Model implements Auditable
{
    use HasFactory , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'department_id',
        'date',
        'balance_id',
        'quantity',
        'recipe_id',
        'total_price',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function balance()
    {
        return $this->belongsTo(Balance::class, 'balance_id');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }
}
