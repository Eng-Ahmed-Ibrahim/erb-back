<?php

namespace App\Transformers\Visitor;

use App\Models\Visitor;
use App\Transformers\BaseTransformer;
use App\Transformers\Booking\AbstractBookingTransformer;

class VisitorTransformer extends AbstractVisitorTransformer
{
    public static function transform(Visitor $visitor)
    {
        $data = parent::transform($visitor);

        $data['current_booking'] = $visitor->getCurrentBooking()
            ? AbstractBookingTransformer::transform($visitor->getCurrentBooking())
            : null;

        $data['total_bookings'] = $visitor->bookings()->count();

        return $data;
    }
}