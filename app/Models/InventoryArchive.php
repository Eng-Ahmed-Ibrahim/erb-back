<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryArchive extends Model
{
    use HasFactory,HasUlids;

    protected $table = 'inventory_archive';

    protected $fillable = [
        'department_id',
        'recipe_id',
        'price',
        'quantity',
        'captured_at',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
