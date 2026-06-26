<?php

namespace Modules\MembershipCards\Application\Handlers;

use Modules\MembershipCards\Application\Commands\CreateFeePlanCommand;
use Modules\MembershipCards\Domain\Entities\FeePlan;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Price;

class CreateFeePlanHandler
{
    public function __construct(
        private FeePlanRepositoryInterface $feePlanRepository
    ) {}

    public function handle(CreateFeePlanCommand $command): FeePlan
    {
        $dto = $command->feePlanDTO;
        
        // Get next version number for this beneficiary type
        $latestPlan = $this->feePlanRepository->findLatestVersion($dto->beneficiaryType);
        $version = $latestPlan ? $latestPlan->getVersion() + 1 : 1;
        
        // NOTE: Deactivation of other plans disabled — allow multiple active plans per beneficiary+weapon
        // if ($dto->active) {
        //     $this->feePlanRepository->deactivateAllByBeneficiaryTypeAndWeapon($dto->beneficiaryType, $dto->weaponType ?? 'infantry');
        // }
        
        $feePlan = new FeePlan(
            name: $dto->name,
            beneficiaryType: $dto->beneficiaryType,
            establishmentFee: new Price($dto->establishmentFee),
            annualSubscriptionFee: new Price($dto->annualSubscriptionFee),
            issuanceFee: new Price($dto->issuanceFee),
            weaponType: $dto->weaponType ?? 'infantry',
            version: $version,
            active: $dto->active,
            description: $dto->description,
            ageRange: $dto->ageRange
        );

        return $this->feePlanRepository->save($feePlan);
    }
}

