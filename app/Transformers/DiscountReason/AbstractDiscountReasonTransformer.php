<?php

namespace App\Transformers\DiscountReason;

use App\Models\DiscountReason;
use App\Transformers\BaseTransformer;

class AbstractDiscountReasonTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(DiscountReason $reason)
    {
        return [
            'id' => (string) $reason->id,
            'discount_reason' => $reason->discount_reason,
            'discount' => $reason->discount,
            'discount_name' => $reason->discount_reason.' - '.$reason->discount.'%',
        ];
    }
}
