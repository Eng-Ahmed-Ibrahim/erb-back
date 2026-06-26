<?php

namespace Modules\ActivitiesSubscriptions\Application\Commands;

use Modules\ActivitiesSubscriptions\Application\DTOs\CreateOfferDTO;

class CreateOfferCommand
{
    public function __construct(
        public readonly CreateOfferDTO $offerDTO
    ) {}
}
