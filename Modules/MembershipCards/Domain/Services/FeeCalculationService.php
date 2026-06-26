<?php

namespace Modules\MembershipCards\Domain\Services;

use Modules\MembershipCards\Domain\Entities\Beneficiary;
use Modules\MembershipCards\Domain\Entities\FeePlan;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Price;

class FeeCalculationService
{
    public function __construct(
        private FeePlanRepositoryInterface $feePlanRepository
    ) {}

    /**
     * Calculate total fees for officer registration
     */
    public function calculateOfficerFees(): array
    {
        $feePlan = $this->feePlanRepository->findActiveByBeneficiaryType('officer');
        
        if (!$feePlan) {
            throw new \RuntimeException('No active fee plan found for officers');
        }
        
        return $this->formatFeeBreakdown($feePlan, true);
    }

    /**
     * Calculate total fees for beneficiary registration
     */
    public function calculateBeneficiaryFees(Beneficiary $beneficiary): array
    {
        $beneficiaryType = $this->determineBeneficiaryType($beneficiary);
        $feePlan = $this->feePlanRepository->findActiveByBeneficiaryType($beneficiaryType);
        
        if (!$feePlan) {
            throw new \RuntimeException("No active fee plan found for beneficiary type: {$beneficiaryType}");
        }
        
        // Check age range if applicable
        if (!$feePlan->matchesAge($beneficiary->getAge())) {
            throw new \RuntimeException("Beneficiary age does not match fee plan requirements");
        }
        
        return $this->formatFeeBreakdown($feePlan, true);
    }

    /**
     * Calculate renewal fees (no establishment fee)
     */
    public function calculateRenewalFees(string $beneficiaryType): array
    {
        return $this->calculateFeesByType($beneficiaryType, false);
    }

    /**
     * Calculate fees by beneficiary type with explicit control over establishment fee inclusion.
     * Use this for preview/calculation endpoints.
     */
    public function calculateFeesByType(string $beneficiaryType, bool $includeEstablishment = true, int $years = 1): array
    {
        $feePlan = $this->feePlanRepository->findActiveByBeneficiaryType($beneficiaryType);
        
        if (!$feePlan) {
            throw new \RuntimeException("No active fee plan found for beneficiary type: {$beneficiaryType}");
        }
        
        return $this->formatFeeBreakdown($feePlan, $includeEstablishment, $years);
    }

    /**
     * Calculate fees using a specific fee plan ID.
     */
    public function calculateFeesByPlanId(int $feePlanId, bool $includeEstablishment = true, int $years = 1): array
    {
        $feePlan = $this->feePlanRepository->findById($feePlanId);
        
        if (!$feePlan) {
            throw new \RuntimeException("Fee plan not found with ID: {$feePlanId}");
        }
        
        return $this->formatFeeBreakdown($feePlan, $includeEstablishment, $years);
    }

    /**
     * Get applicable fee plan for a beneficiary
     */
    public function getApplicableFeePlan(Beneficiary $beneficiary): ?FeePlan
    {
        $beneficiaryType = $this->determineBeneficiaryType($beneficiary);
        return $this->feePlanRepository->findActiveByBeneficiaryType($beneficiaryType);
    }

    /**
     * Determine beneficiary type based on relationship and age
     */
    public function determineBeneficiaryType(Beneficiary $beneficiary): string
    {
        $relationshipType = $beneficiary->getRelationshipType();
        $age = $beneficiary->getAge();
        
        switch ($relationshipType) {
            case 'spouse':
                return 'spouse';
                
            case 'child':
                if ($beneficiary->isUnder21()) {
                    return 'child_under_21';
                }
                return 'child_graduate';
                
            case 'parent':
                return 'parent';
                
            case 'grandchild':
                if ($age !== null) {
                    if ($age >= 6 && $age <= 10) {
                        return 'grandchild_6_10';
                    } elseif ($age >= 11 && $age <= 19) {
                        return 'grandchild_11_19';
                    } elseif ($age >= 20) {
                        return 'grandchild_20_plus';
                    }
                }
                return 'grandchild';
                
            case 'child_spouse':
                return 'child_spouse';
            
            case 'brother':
                return 'brother';
            
            case 'sister':
                return 'sister';
            
            case 'sister_spouse':
                return 'sister_spouse';

            case 'over_age':
                return 'over_age';
                
            default:
                return $relationshipType;
        }
    }

    /**
     * Format fee breakdown for response
     */
    private function formatFeeBreakdown(FeePlan $feePlan, bool $includeEstablishment, int $years = 1): array
    {
        $years = max(1, $years);
        
        $establishmentFee = $includeEstablishment 
            ? $feePlan->getEstablishmentFee() 
            : Price::zero();
            
        $annualFeePerYear = $feePlan->getAnnualSubscriptionFee();
        $annualFeeTotal = new Price($annualFeePerYear->getAmount() * $years);
        $issuanceFee = $feePlan->getIssuanceFee();
        
        $total = $establishmentFee->add($annualFeeTotal)->add($issuanceFee);
        
        return [
            'fee_plan_id' => $feePlan->getId(),
            'fee_plan_name' => $feePlan->getName(),
            'establishment_fee' => $establishmentFee->getAmount(),
            'annual_subscription_fee_per_year' => $annualFeePerYear->getAmount(),
            'annual_subscription_fee' => $annualFeeTotal->getAmount(),
            'years' => $years,
            'issuance_fee' => $issuanceFee->getAmount(),
            'total' => $total->getAmount(),
            'currency' => $establishmentFee->getCurrency(),
        ];
    }
}

