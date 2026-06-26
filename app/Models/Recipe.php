<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class Recipe extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'image',
        'recipe_category_id',
        'unit_id',
        'minimum_limt',
        'days_before_expire',
        'status',
        'created_at',
    ];

    public function recipeCategory()
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot('quantity', 'price', 'total_price', 'status', 'expire_date')
            ->using(new class extends Pivot
            {
                use HasUlids;
            })
            ->withTimestamps();
    }

    public function requests()
    {
        return $this->belongsToMany(Request::class)
            ->withPivot('quantity')
            ->using(new class extends Pivot
            {
                use HasUlids;
            })
            ->withTimestamps();
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_store')
            ->withPivot('quantity', 'price')
            ->using(new class extends Pivot
            {
                use HasUlids;
            })
            ->withTimestamps();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity')
            ->withTimestamps()
            ->using(new class extends Pivot
            {
                use HasUlids;
            });
    }

    public function recipeBalances()
    {
        return $this->hasMany(RecipeBalance::class, 'recipe_id');
    }
}
