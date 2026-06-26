<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;
class Invoice extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $table = 'invoices';

    protected $fillable = [
        'from',
        'to',
        'supplier_id',
        'code',
        'invoice_date',
        'status',
        'invoice_price',
        'total_price',
        'image',
        'type',
        'discount',
        'tax',
        'note',
        'is_paid',
        'created_by',
        'is_closed',
        'created_at',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class)
            ->withPivot('quantity', 'price', 'total_price', 'status', 'expire_date', 'source_invoice_id')
            ->using(new class extends Pivot
            {
                use HasUlids;
            })
            ->withTimestamps();
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to');
    }

    public static function boot()
    {
        parent::boot();

        $user = auth('api')->user();

        if ($user && $user->department && $user->department->type !== 'master') {
            $department = $user->department;

            static::addGlobalScope('invoices', function (Builder $builder) {
                // if ($builder->getModel()->type !== 'transfer') {

                // $builder->where('from', $department->id)->orWhere('to', $department->id);

                //     }
            });
        }
    }
}
