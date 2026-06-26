<?php

namespace App\Transformers\Department;

use App\Models\Department;
use App\Transformers\BaseTransformer;

class AbstractDepartmentTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @return array
     */
    public static function transform(Department $department)
    {
        $invoices_price = $department->toInvoices()->get()->sum('invoice_price');

        return [
            'id' => (string) $department->id,
            'name' => $department->name,
            'image' => $department->image ? (string) config('app.url').$department->image : '',
            'code' => $department->code,
            'phone' => $department->phone,
            'type' => $department->type,
            'total_invoices_price' => number_format($invoices_price, 3, '.', ''),
        ];
    }
}
