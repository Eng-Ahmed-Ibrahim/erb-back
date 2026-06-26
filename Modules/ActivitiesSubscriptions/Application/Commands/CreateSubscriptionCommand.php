<?php

namespace Modules\ActivitiesSubscriptions\Application\Commands;

use Modules\ActivitiesSubscriptions\Application\DTOs\CreateSubscriptionDTO;

class CreateSubscriptionCommand
{
    public function __construct(
        public readonly CreateSubscriptionDTO $subscriptionDTO
    ) {}
}
