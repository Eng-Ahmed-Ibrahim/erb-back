<?php

namespace Modules\MembershipCards\Application\Commands;

use Modules\MembershipCards\Application\DTOs\CreateFeePlanDTO;

class CreateFeePlanCommand
{
    public function __construct(
        public readonly CreateFeePlanDTO $feePlanDTO
    ) {}
}

