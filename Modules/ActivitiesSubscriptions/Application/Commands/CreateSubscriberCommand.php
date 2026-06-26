<?php

namespace Modules\ActivitiesSubscriptions\Application\Commands;

use Modules\ActivitiesSubscriptions\Application\DTOs\CreateSubscriberDTO;

class CreateSubscriberCommand
{
    public function __construct(
        public readonly CreateSubscriberDTO $subscriberDTO
    ) {}
}
