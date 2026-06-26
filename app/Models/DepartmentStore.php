<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class DepartmentStore extends Pivot implements Auditable
{
    use HasFactory,  HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $table = 'department_store';

    protected $casts = ['id' => 'string'];

    protected $fillable = [
        'department_id',
        'recipe_id',
        'quantity',
        'over_quantity',
        'price',
        'actual_quantity',
        'under_quantity',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function quantites()
    {
        return $this->hasMany(RecipeQuantity::class, 'department_store_id');
    }
}
