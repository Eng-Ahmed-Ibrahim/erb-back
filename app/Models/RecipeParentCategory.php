<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RecipeParentCategory extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    const CHEMICAL_PARENT_CATEGORY_ID = '01jc3zy5etcng2sc0jr0b2qtds';

    const MAINTAINANCE_PARENT_CATEGORY_ID = '01jc3zx5m1v8frvhgj20fyck4x';

    const MANAFEZ_PARENT_CATEGORY_ID = '01jc6ncn19a6mcq01351kz74n4';
    // const

    protected $fillable = [
        'name',
        'description',
        'image',
        'warehouse_section_id',
    ];

    public function subCategories()
    {
        return $this->hasMany(RecipeCategory::class, 'category_id');
    }
}
