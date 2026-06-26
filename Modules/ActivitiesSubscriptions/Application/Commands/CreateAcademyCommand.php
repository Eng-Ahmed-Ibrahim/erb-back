<?php

namespace Modules\ActivitiesSubscriptions\Application\Commands;

use Modules\ActivitiesSubscriptions\Application\DTOs\CreateAcademyDTO;

class CreateAcademyCommand
{
    public function __construct(
        public readonly CreateAcademyDTO $academyDTO
    ) {}
}
