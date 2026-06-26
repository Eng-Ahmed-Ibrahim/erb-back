<?php

namespace Modules\MembershipCards\Application\Commands;

use Modules\MembershipCards\Application\DTOs\CreateOfficerDTO;

class CreateOfficerCommand
{
    public function __construct(
        public readonly CreateOfficerDTO $officerDTO
    ) {}
}

