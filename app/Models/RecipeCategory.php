<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RecipeCategory extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'description',
        'image',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(RecipeParentCategory::class, 'category_id');
    }

    public function recipeCategory()
    {
        return $this->belongsTo(RecipeParentCategory::class, 'category_id');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'recipe_category_id');
    }
}
