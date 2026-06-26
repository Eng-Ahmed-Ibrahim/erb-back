<?php

namespace Modules\MembershipCards\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\MembershipCards\Application\Commands\CreateSubscriptionCommand;
use Modules\MembershipCards\Application\Commands\RenewSubscriptionCommand;
use Modules\MembershipCards\Application\DTOs\CreateSubscriptionDTO;
use Modules\MembershipCards\Application\Handlers\CreateSubscriptionHandler;
use Modules\MembershipCards\Application\Handlers\RenewSubscriptionHandler;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;
use Modules\MembershipCards\Domain\Services\FeeCalculationService;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private OfficerRepositoryInterface $officerRepository,
        private BeneficiaryRepositoryInterface $beneficiaryRepository,
        private FeePlanRepositoryInterface $feePlanRepository,
        private MembershipCardRepositoryInterface $membershipCardRepository,
        private CreateSubscriptionHandler $createSubscriptionHandler,
        private RenewSubscriptionHandler $renewSubscriptionHandler,
        private FeeCalculationService $feeCalculationService
    ) {}

    /**
     * Display a paginated listing of subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $perPage = $request->integer('per_page', 15);

        $result = $this->subscriptionRepository->paginate($perPage, $status);

        $result['data'] = array_map([$this, 'transformSubscriptionToArray'], $result['data']);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }

    /**
     * Store a newly created subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'officer_id' => 'required|integer|exists:mc_officers,id',
            'beneficiary_id' => 'nullable|integer|exists:mc_beneficiaries,id',
            'fee_plan_id' => 'required|integer|exists:mc_fee_plans,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'paid_establishment_fee' => 'numeric|min:0',
            'paid_annual_fee' => 'numeric|min:0',
            'paid_issuance_fee' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'is_honorary_membership' => 'nullable|boolean',
            'is_old_officer' => 'nullable|boolean'
        ]);

        try {
            $data = $request->all();

            // Get authenticated user or first user as fallback
            $user = $request->user();
            if (!$user) {
                $user = \App\Models\User::first();
            }

            if (!$user) {
                throw new \Exception('No user found for subscription creation');
            }

            $data['created_by'] = $user->id;
            
            $dto = CreateSubscriptionDTO::fromArray($data);
            $command = new CreateSubscriptionCommand($dto);
            $subscription = $this->createSubscriptionHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $this->transformSubscriptionToArray($subscription),
                'message' => 'تم إنشاء الاشتراك بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified subscription.
     */
    public function show(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSubscriptionToArray($subscription)
        ]);
    }

    /**
     * Get subscriptions by officer.
     */
    public function getByOfficer(int $officerId): JsonResponse
    {
        $subscriptions = $this->subscriptionRepository->findByOfficerId($officerId);
        $transformedSubscriptions = array_map([$this, 'transformSubscriptionToArray'], $subscriptions);

        return response()->json([
            'success' => true,
            'data' => $transformedSubscriptions
        ]);
    }

    /**
     * Suspend a subscription.
     */
    public function suspend(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        try {
            $subscription->suspend();
            $this->subscriptionRepository->save($subscription);

            return response()->json([
                'success' => true,
                'data' => $this->transformSubscriptionToArray($subscription),
                'message' => 'تم إيقاف الاشتراك'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Activate a subscription.
     */
    public function activate(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        try {
            $subscription->activate();
            $this->subscriptionRepository->save($subscription);

            return response()->json([
                'success' => true,
                'data' => $this->transformSubscriptionToArray($subscription),
                'message' => 'تم تفعيل الاشتراك'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Renew a subscription.
     */
    public function renew(int $id, Request $request): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'الاشتراك غير موجود'
            ], 404);
        }

        $request->validate([
            'new_end_date' => 'required|date|after:' . $subscription->getEndDate()->format('Y-m-d'),
            'paid_annual_fee' => 'required|numeric|min:0',
            'paid_issuance_fee' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $command = new RenewSubscriptionCommand(
                subscriptionId: $id,
                newEndDate: $request->input('new_end_date'),
                paidAnnualFee: $request->input('paid_annual_fee'),
                paidIssuanceFee: $request->input('paid_issuance_fee'),
                notes: $request->input('notes')
            );

            $this->renewSubscriptionHandler->handle($command);

            // Fetch updated subscription
            $updatedSubscription = $this->subscriptionRepository->findById($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformSubscriptionToArray($updatedSubscription),
                'message' => 'تم تجديد الاشتراك بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get expiring subscriptions.
     */
    public function expiring(Request $request): JsonResponse
    {
        $daysAhead = $request->integer('days', 30);
        $subscriptions = $this->subscriptionRepository->findExpiring($daysAhead);
        $transformedSubscriptions = array_map([$this, 'transformSubscriptionToArray'], $subscriptions);

        return response()->json([
            'success' => true,
            'data' => $transformedSubscriptions
        ]);
    }

    /**
     * Calculate fees for a new subscription.
     */
    public function calculateFees(Request $request): JsonResponse
    {
        $request->validate([
            'beneficiary_type' => 'nullable|string',
            'fee_plan_id' => 'nullable|integer|exists:mc_fee_plans,id',
            'is_renewal' => 'boolean',
            'is_old_officer' => 'boolean',
            'years' => 'integer|min:1'
        ]);

        try {
            $isRenewal = $request->boolean('is_renewal', false);
            $isOldOfficer = $request->boolean('is_old_officer', false);
            $years = $request->integer('years', 1);

            // Include establishment fee only for new subscriptions of non-old officers
            $includeEstablishment = !$isRenewal && !$isOldOfficer;

            // Use specific fee plan if provided, otherwise fall back to beneficiary type lookup
            if ($request->filled('fee_plan_id')) {
                $fees = $this->feeCalculationService->calculateFeesByPlanId(
                    $request->integer('fee_plan_id'),
                    $includeEstablishment,
                    $years
                );
            } else {
                $fees = $this->feeCalculationService->calculateFeesByType(
                    $request->beneficiary_type,
                    $includeEstablishment,
                    $years
                );
            }

            return response()->json([
                'success' => true,
                'data' => $fees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $this->subscriptionRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاشتراك بنجاح'
        ]);
    }

    /**
     * Transform Subscription entity to array for JSON response.
     */
    private function transformSubscriptionToArray($subscription): array
    {
        // Fetch related entities
        $officer = $this->officerRepository->findById($subscription->getOfficerId());
        $beneficiary = $subscription->getBeneficiaryId()
            ? $this->beneficiaryRepository->findById($subscription->getBeneficiaryId())
            : null;
        $feePlan = $this->feePlanRepository->findById($subscription->getFeePlanId());
        
        // Check if subscription has an active card
        $card = $this->membershipCardRepository->findBySubscriptionId($subscription->getId());
        $hasCard = $card !== null && $card->isActive();

        return [
            'id' => $subscription->getId(),
            'officer_id' => $subscription->getOfficerId(),
            'beneficiary_id' => $subscription->getBeneficiaryId(),
            'fee_plan_id' => $subscription->getFeePlanId(),
            'start_date' => $subscription->getStartDate()->format('Y-m-d'),
            'end_date' => $subscription->getEndDate()->format('Y-m-d'),
            'status' => $subscription->getStatus(),
            'is_active' => $subscription->isActive(),
            'is_expired' => $subscription->isExpired(),
            'is_for_officer' => $subscription->isForOfficer(),
            'paid_establishment_fee' => $subscription->getPaidEstablishmentFee()->getAmount(),
            'paid_annual_fee' => $subscription->getPaidAnnualFee()->getAmount(),
            'paid_issuance_fee' => $subscription->getPaidIssuanceFee()->getAmount(),
            'total_paid' => $subscription->getTotalPaidAmount()->getAmount(),
            'created_by' => $subscription->getCreatedBy(),
            'notes' => $subscription->getNotes(),
            'is_honorary_membership' => $subscription->isHonoraryMembership(),
            'can_renew' => $subscription->canRenew(),
            'has_card' => $hasCard,
            // Include related entities
            'officer' => $officer ? [
                'id' => $officer->getId(),
                'full_name' => $officer->getFullName(),
                'rank' => $officer->getRank(),
                'military_number' => $officer->getMilitaryNumber()->getValue(),
                'membership_id' => $officer->getMembershipId(),
                'weapon_type' => $officer->getWeaponType(),
            ] : null,
            'beneficiary' => $beneficiary ? [
                'id' => $beneficiary->getId(),
                'full_name' => $beneficiary->getFullName(),
                'relationship_type' => $beneficiary->getRelationshipType(),
            ] : null,
            'fee_plan' => $feePlan ? [
                'id' => $feePlan->getId(),
                'name' => $feePlan->getName(),
                'beneficiary_type' => $feePlan->getBeneficiaryType(),
                'weapon_type' => $feePlan->getWeaponType(),
            ] : null,
        ];
    }

    /**
     * Get subscriptions report with financial summary.
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $subscriptions = $this->subscriptionRepository->findByDateRange($fromDate, $toDate);
        
        // Calculate financial totals
        $totalEstablishmentFee = 0;
        $totalAnnualFee = 0;
        $totalIssuanceFee = 0;
        $totalRevenue = 0;
        
        $transformedSubscriptions = [];
        
        foreach ($subscriptions as $subscription) {
            $feePlan = $this->feePlanRepository->findById($subscription->getFeePlanId());
            
            if (!$feePlan) {
                // Skip if fee plan not found
                continue;
            }
            
            // Use actual paid amounts stored on the subscription
            $establishmentFee = (float) $subscription->getPaidEstablishmentFee()->getAmount();
            $annualFee = (float) $subscription->getPaidAnnualFee()->getAmount();
            $issuanceFee = (float) $subscription->getPaidIssuanceFee()->getAmount();
            $subscriptionTotal = $establishmentFee + $annualFee + $issuanceFee;
            
            $totalEstablishmentFee += $establishmentFee;
            $totalAnnualFee += $annualFee;
            $totalIssuanceFee += $issuanceFee;
            $totalRevenue += $subscriptionTotal;
            
            $transformedSubscriptions[] = array_merge(
                $this->transformSubscriptionToArray($subscription),
                [
                    'establishment_fee' => $establishmentFee,
                    'annual_fee' => $annualFee,
                    'issuance_fee' => $issuanceFee,
                    'total_amount' => $subscriptionTotal,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscriptions' => $transformedSubscriptions,
                'summary' => [
                    'total_count' => count($subscriptions),
                    'total_establishment_fee' => $totalEstablishmentFee,
                    'total_annual_fee' => $totalAnnualFee,
                    'total_issuance_fee' => $totalIssuanceFee,
                    'total_revenue' => $totalRevenue,
                ],
                'date_range' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
            ],
        ]);
    }
}

