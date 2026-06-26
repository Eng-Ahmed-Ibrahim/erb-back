<?php

namespace Modules\MembershipCards\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\MembershipCards\Application\Commands\CreateFeePlanCommand;
use Modules\MembershipCards\Application\DTOs\CreateFeePlanDTO;
use Modules\MembershipCards\Application\Handlers\CreateFeePlanHandler;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Price;

class FeePlanController extends Controller
{
    public function __construct(
        private FeePlanRepositoryInterface $feePlanRepository,
        private CreateFeePlanHandler $createFeePlanHandler
    ) {}

    /**
     * Display a listing of fee plans.
     */
    public function index(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', false);
        $weaponType = $request->get('weapon_type');

        if ($weaponType) {
            if ($activeOnly) {
                $feePlans = $this->feePlanRepository->findActiveByWeaponType($weaponType);
            } else {
                $feePlans = $this->feePlanRepository->findByWeaponType($weaponType);
            }
        } elseif ($activeOnly) {
            $feePlans = $this->feePlanRepository->findActive();
        } else {
            $feePlans = $this->feePlanRepository->findAll();
        }

        $transformedFeePlans = array_map([$this, 'transformFeePlanToArray'], $feePlans);

        // Group by weapon type for better organization
        $grouped = [
            'infantry' => [],
            'other' => [],
        ];

        foreach ($transformedFeePlans as $plan) {
            $wt = $plan['weapon_type'] ?? 'infantry';
            if (isset($grouped[$wt])) {
                $grouped[$wt][] = $plan;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $transformedFeePlans,
            'grouped' => $grouped
        ]);
    }

    /**
     * Store a newly created fee plan.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'beneficiary_type' => 'required|string|max:100',
            'weapon_type' => 'nullable|string|in:infantry,other',
            'establishment_fee' => 'required|numeric|min:0',
            'annual_subscription_fee' => 'required|numeric|min:0',
            'issuance_fee' => 'required|numeric|min:0',
            'active' => 'boolean',
            'description' => 'nullable|string',
            'age_range' => 'nullable|array',
            'age_range.min' => 'nullable|integer|min:0',
            'age_range.max' => 'nullable|integer|min:0'
        ]);

        try {
            $dto = CreateFeePlanDTO::fromArray($request->all());
            $command = new CreateFeePlanCommand($dto);
            $feePlan = $this->createFeePlanHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $this->transformFeePlanToArray($feePlan),
                'message' => 'تم إنشاء خطة الرسوم بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified fee plan.
     */
    public function show(int $id): JsonResponse
    {
        $feePlan = $this->feePlanRepository->findById($id);
        
        if (!$feePlan) {
            return response()->json([
                'success' => false,
                'message' => 'Fee plan not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformFeePlanToArray($feePlan)
        ]);
    }

    /**
     * Update the specified fee plan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $feePlan = $this->feePlanRepository->findById($id);
        
        if (!$feePlan) {
            return response()->json([
                'success' => false,
                'message' => 'Fee plan not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'beneficiary_type' => 'sometimes|string|max:100',
            'weapon_type' => 'sometimes|string|in:infantry,other',
            'establishment_fee' => 'sometimes|numeric|min:0',
            'annual_subscription_fee' => 'sometimes|numeric|min:0',
            'issuance_fee' => 'sometimes|numeric|min:0',
            'active' => 'boolean',
            'description' => 'nullable|string',
            'age_range' => 'nullable|array'
        ]);

        try {
            if ($request->has('name')) {
                $feePlan->setName($request->name);
            }
            if ($request->has('beneficiary_type')) {
                $feePlan->setBeneficiaryType($request->beneficiary_type);
            }
            if ($request->has('weapon_type')) {
                $feePlan->setWeaponType($request->weapon_type);
            }
            if ($request->has('establishment_fee')) {
                $feePlan->setEstablishmentFee(new Price($request->establishment_fee));
            }
            if ($request->has('annual_subscription_fee')) {
                $feePlan->setAnnualSubscriptionFee(new Price($request->annual_subscription_fee));
            }
            if ($request->has('issuance_fee')) {
                $feePlan->setIssuanceFee(new Price($request->issuance_fee));
            }
            if ($request->has('active')) {
                if ($request->active) {
                    // NOTE: Deactivation of other plans disabled — allow multiple active plans per beneficiary+weapon
                    // $this->feePlanRepository->deactivateAllByBeneficiaryTypeAndWeapon($feePlan->getBeneficiaryType(), $feePlan->getWeaponType());
                    $feePlan->activate();
                } else {
                    $feePlan->deactivate();
                }
            }
            if ($request->has('description')) {
                $feePlan->setDescription($request->description);
            }
            if ($request->has('age_range')) {
                $feePlan->setAgeRange($request->age_range);
            }

            $updatedFeePlan = $this->feePlanRepository->save($feePlan);

            return response()->json([
                'success' => true,
                'data' => $this->transformFeePlanToArray($updatedFeePlan),
                'message' => 'تم تحديث خطة الرسوم بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified fee plan.
     */
    public function destroy(int $id): JsonResponse
    {
        $feePlan = $this->feePlanRepository->findById($id);
        
        if (!$feePlan) {
            return response()->json([
                'success' => false,
                'message' => 'Fee plan not found'
            ], 404);
        }

        $this->feePlanRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف خطة الرسوم بنجاح'
        ]);
    }

    /**
     * Get active fee plan by beneficiary type.
     */
    public function getByBeneficiaryType(string $beneficiaryType): JsonResponse
    {
        $feePlan = $this->feePlanRepository->findActiveByBeneficiaryType($beneficiaryType);
        
        if (!$feePlan) {
            return response()->json([
                'success' => false,
                'message' => 'No active fee plan found for this beneficiary type'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformFeePlanToArray($feePlan)
        ]);
    }

    /**
     * Get fee plans by weapon type.
     */
    public function getByWeaponType(Request $request, string $weaponType): JsonResponse
    {
        if (!in_array($weaponType, ['infantry', 'other'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid weapon type. Must be "infantry" or "other"'
            ], 400);
        }

        $activeOnly = $request->boolean('active_only', true);

        if ($activeOnly) {
            $feePlans = $this->feePlanRepository->findActiveByWeaponType($weaponType);
        } else {
            $feePlans = $this->feePlanRepository->findByWeaponType($weaponType);
        }

        $transformedFeePlans = array_map([$this, 'transformFeePlanToArray'], $feePlans);

        return response()->json([
            'success' => true,
            'data' => $transformedFeePlans,
            'weapon_type' => $weaponType,
            'weapon_type_label' => $weaponType === 'infantry' ? 'ضباط المشاة' : 'ضباط الأسلحة الأخرى'
        ]);
    }

    /**
     * Transform FeePlan entity to array for JSON response.
     */
    private function transformFeePlanToArray($feePlan): array
    {
        return [
            'id' => $feePlan->getId(),
            'name' => $feePlan->getName(),
            'beneficiary_type' => $feePlan->getBeneficiaryType(),
            'weapon_type' => $feePlan->getWeaponType(),
            'weapon_type_label' => $feePlan->getWeaponType() === 'infantry' ? 'مشاة' : 'أسلحة أخرى',
            'establishment_fee' => $feePlan->getEstablishmentFee()->getAmount(),
            'annual_subscription_fee' => $feePlan->getAnnualSubscriptionFee()->getAmount(),
            'issuance_fee' => $feePlan->getIssuanceFee()->getAmount(),
            'total_initial_fee' => $feePlan->getTotalInitialFee()->getAmount(),
            'renewal_fee' => $feePlan->getRenewalFee()->getAmount(),
            'version' => $feePlan->getVersion(),
            'active' => $feePlan->isActive(),
            'description' => $feePlan->getDescription(),
            'age_range' => $feePlan->getAgeRange(),
        ];
    }
}

