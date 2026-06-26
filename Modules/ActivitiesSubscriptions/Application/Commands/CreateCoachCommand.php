<?php

namespace Modules\ActivitiesSubscriptions\Application\Commands;

use Modules\ActivitiesSubscriptions\Application\DTOs\CreateCoachDTO;

class CreateCoachCommand
{
    public function __construct(
        public readonly CreateCoachDTO $dto
    ) {}
}
