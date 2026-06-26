<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ModelHasParentCategory extends Model implements Auditable
{
    use HasFactory , \OwenIt\Auditing\Auditable;

    protected $table = 'model_has_parent_recipe_category';

    protected $fillable = [
        'model',
        'model_id',
        'category_id',
    ];
}
