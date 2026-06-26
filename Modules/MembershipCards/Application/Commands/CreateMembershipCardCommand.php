<?php

namespace Modules\MembershipCards\Application\Commands;

use Modules\MembershipCards\Application\DTOs\CreateMembershipCardDTO;

class CreateMembershipCardCommand
{
    public function __construct(
        public readonly CreateMembershipCardDTO $cardDTO
    ) {}
}

