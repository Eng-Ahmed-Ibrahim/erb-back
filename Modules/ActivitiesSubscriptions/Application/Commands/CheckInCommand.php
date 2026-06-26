<?php

namespace Modules\ActivitiesSubscriptions\Application\Commands;

use Carbon\Carbon;

class CheckInCommand
{
    public function __construct(
        public readonly string $qrCode,
        public readonly Carbon $checkInDate,
        public readonly string $dayOfWeek
    ) {}
}
