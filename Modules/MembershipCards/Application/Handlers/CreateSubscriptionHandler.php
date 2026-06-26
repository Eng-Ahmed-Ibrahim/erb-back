<?php

namespace Modules\MembershipCards\Application\Handlers;

use Carbon\Carbon;
use Modules\MembershipCards\Application\Commands\CreateSubscriptionCommand;
use Modules\MembershipCards\Domain\Entities\Subscription;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Duration;
use Modules\MembershipCards\Domain\ValueObjects\Price;

class CreateSubscriptionHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private OfficerRepositoryInterface $officerRepository,
        private BeneficiaryRepositoryInterface $beneficiaryRepository,
        private FeePlanRepositoryInterface $feePlanRepository
    ) {}

    public function handle(CreateSubscriptionCommand $command): Subscription
    {
        $dto = $command->subscriptionDTO;
        
        // Validate officer exists
        if (!$this->officerRepository->exists($dto->officerId)) {
            throw new \InvalidArgumentException('الضابط غير موجود');
        }
        
        // Validate beneficiary exists (if provided)
        if ($dto->beneficiaryId !== null && !$this->beneficiaryRepository->exists($dto->beneficiaryId)) {
            throw new \InvalidArgumentException('المستفيد غير موجود');
        }
        
        // Validate fee plan exists and is active
        $feePlan = $this->feePlanRepository->findById($dto->feePlanId);
        if (!$feePlan) {
            throw new \InvalidArgumentException('خطة الرسوم غير موجودة');
        }
        if (!$feePlan->isActive()) {
            throw new \InvalidArgumentException('خطة الرسوم غير نشطة');
        }
        
        // Check for existing active subscription
        if ($this->subscriptionRepository->hasActiveSubscription($dto->officerId, $dto->beneficiaryId)) {
            throw new \InvalidArgumentException('يوجد اشتراك نشط بالفعل');
        }

        // Calculate subscription duration in years (rounded up)
        $startDate = Carbon::parse($dto->startDate);
        $endDate = Carbon::parse($dto->endDate);
        $years = max(1, (int) ceil($startDate->floatDiffInYears($endDate)));

        // Calculate fees from fee plan
        // Old officers (transferred/existing members) are exempt from establishment fee
        $establishmentFee = $dto->isOldOfficer 
            ? 0.0 
            : (float) $feePlan->getEstablishmentFee()->getAmount();
        $annualFeePerYear = (float) $feePlan->getAnnualSubscriptionFee()->getAmount();
        $annualFee = $annualFeePerYear * $years;
        $issuanceFee = (float) $feePlan->getIssuanceFee()->getAmount();

        // Use explicitly provided fees if they are non-zero, otherwise use calculated fees
        $paidEstablishment = $dto->paidEstablishmentFee > 0 
            ? $dto->paidEstablishmentFee 
            : $establishmentFee;
        $paidAnnual = $dto->paidAnnualFee > 0 
            ? $dto->paidAnnualFee 
            : $annualFee;
        $paidIssuance = $dto->paidIssuanceFee > 0 
            ? $dto->paidIssuanceFee 
            : $issuanceFee;

        // Enforce: old officer always has 0 establishment fee regardless of what was sent
        if ($dto->isOldOfficer) {
            $paidEstablishment = 0.0;
        }
        
        $subscription = new Subscription(
            officerId: $dto->officerId,
            feePlanId: $dto->feePlanId,
            duration: new Duration(
                Carbon::parse($dto->startDate),
                Carbon::parse($dto->endDate)
            ),
            createdBy: $dto->createdBy,
            paidEstablishmentFee: new Price($paidEstablishment),
            paidAnnualFee: new Price($paidAnnual),
            paidIssuanceFee: new Price($paidIssuance),
            beneficiaryId: $dto->beneficiaryId,
            notes: $dto->notes,
            isHonoraryMembership: $dto->isHonoraryMembership
        );

        return $this->subscriptionRepository->save($subscription);
    }
}

