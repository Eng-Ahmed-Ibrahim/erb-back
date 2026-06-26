<?php

namespace Modules\MembershipCards\Application\Commands;

use Modules\MembershipCards\Application\DTOs\CreateBeneficiaryDTO;

class CreateBeneficiaryCommand
{
    public function __construct(
        public readonly CreateBeneficiaryDTO $beneficiaryDTO
    ) {}
}

