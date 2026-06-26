<?php

namespace App\Transformers\Supplier;

use App\Models\Supplier;
use App\Transformers\BaseTransformer;

class SupplierTransformer extends BaseTransformer
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
    public static function transform(Supplier $supplier, $data = [])
    {

        $invoices_price = $supplier->invoices()->get()->sum('invoice_price');

        return [
            'id' => (string) $supplier->id,
            'name' => $supplier->name,
            'phone' => $supplier->phone,
            'type' => $supplier->type,
            'address' => $supplier->address,
            'total_invoices_price' => $invoices_price,
        ];
    }
}
