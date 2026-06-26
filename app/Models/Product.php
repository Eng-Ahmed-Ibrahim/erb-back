<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'id',
        'name',
        'image',
        'offer',
        'category_id',
        'sub_category_id',
        'description',
        'status',
        'type',
        'price',
        'deleted_at',
        // 'quantity'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(Subcategory::class, 'sub_category_id');
    }

    public function prices()
    {
        return $this->hasMany(Price::class, 'product_id')->orderBy('created_at', 'asc');
    }

    // public function price()
    // {
    //     return $this->hasOne(Price::class, 'product_id')->where('default', 1);
    // }
    public function departments()
    {
        return $this->belongsToMany(Department::class)->using(new class extends Pivot
        {
            use HasUlids;
        });
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class)->withPivot('quantity')->using(new class extends Pivot
        {
            use HasUlids;
        });
    }

    public function removeRecipe($recipeId)
    {
        return $this->recipes()->detach($recipeId);
    }

    public function orders()
    {
        return $this->hasMany(OrderProduct::class, 'product_id');
    }
}
