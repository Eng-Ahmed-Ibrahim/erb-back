<?php

namespace Modules\MembershipCards\Application\Commands;

use Modules\MembershipCards\Application\DTOs\CreateSubscriptionDTO;

class CreateSubscriptionCommand
{
    public function __construct(
        public readonly CreateSubscriptionDTO $subscriptionDTO
    ) {}
}

