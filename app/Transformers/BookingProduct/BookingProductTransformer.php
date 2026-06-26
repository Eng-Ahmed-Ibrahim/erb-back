<?php

namespace App\Transformers\BookingProduct;

use App\Models\BookingProduct;
use App\Transformers\BaseTransformer;
use App\Transformers\Product\AbstractProductTransformer;

class BookingProductTransformer extends BaseTransformer
{
    protected $relations = ['product'];

    protected $load = [];

    public static function transform(BookingProduct $bookingProduct)
    {
        return [
            'id' => (string) $bookingProduct->id,
            'booking_id' => (string) $bookingProduct->booking_id,
            'product_id' => (string) $bookingProduct->product_id,
            'quantity' => $bookingProduct->quantity,
            'unit_price' => (float) $bookingProduct->unit_price,
            'total_price' => (float) $bookingProduct->total_price,
            'notes' => $bookingProduct->notes,
            'formatted_unit_price' => $bookingProduct->formatted_unit_price,
            'formatted_total_price' => $bookingProduct->formatted_total_price,
            'product' => $bookingProduct->product 
                ? AbstractProductTransformer::transform($bookingProduct->product)
                : null,
            'created_at' => $bookingProduct->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $bookingProduct->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
} 