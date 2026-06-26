<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Supplier extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'type',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'supplier_id');
    }

    const NOZOM_BLANCES_ADJUSTING_SUPPLIER = '01jsz05683cwdxyvm0qqg26mq1';
}
