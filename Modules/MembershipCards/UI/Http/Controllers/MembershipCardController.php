<?php

namespace Modules\MembershipCards\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\MembershipCards\Application\Commands\CreateMembershipCardCommand;
use Modules\MembershipCards\Application\DTOs\CreateMembershipCardDTO;
use Modules\MembershipCards\Application\Handlers\CreateMembershipCardHandler;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Modules\MembershipCards\Domain\Services\CardValidationService;
use Modules\MembershipCards\Domain\Services\CardWriterService;
use App\Models\Setting;

class MembershipCardController extends Controller
{
    public function __construct(
        private MembershipCardRepositoryInterface $cardRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private OfficerRepositoryInterface $officerRepository,
        private BeneficiaryRepositoryInterface $beneficiaryRepository,
        private CreateMembershipCardHandler $createCardHandler,
        private CardValidationService $cardValidationService,
        private CardWriterService $cardWriterService
    ) {}

    /**
     * Display a listing of membership cards.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        
        if ($status) {
            $cards = $this->cardRepository->findByStatus($status);
        } else {
            $cards = $this->cardRepository->findAll();
        }

        $transformedCards = array_map([$this, 'transformCardToArray'], $cards);

        return response()->json([
            'success' => true,
            'data' => $transformedCards
        ]);
    }

    /**
     * Issue a new membership card.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|integer|exists:mc_subscriptions,id',
            'card_uid' => 'required|string|max:20',
            'expiry_date' => 'required|date',
            'notes' => 'nullable|string',
            'serial_id' => 'nullable|string|max:32'
        ]);

        // Validate card UID format
        if (!$this->cardValidationService->validateCardUidFormat($request->card_uid)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid card UID format. Must be valid MIFARE UID (4, 7, or 10 bytes in hex)'
            ], 422);
        }

        try {
            $dto = CreateMembershipCardDTO::fromArray($request->all());
            $command = new CreateMembershipCardCommand($dto);
            $card = $this->createCardHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $this->transformCardToArray($card),
                'message' => 'تم إصدار الكارت بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified card.
     */
    public function show(int $id): JsonResponse
    {
        $card = $this->cardRepository->findById($id);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformCardToArray($card)
        ]);
    }

    /**
     * Validate card by UID.
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'card_uid' => 'required|string'
        ]);

        $result = $this->cardValidationService->validateByCardUid($request->card_uid);

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => $result['valid'],
                'errors' => $result['errors'] ?? [],
                'card' => $result['card'] ? $this->transformCardToArray($result['card']) : null,
                'days_until_expiry' => $result['days_until_expiry'] ?? null,
            ]
        ]);
    }

    /**
     * Mark card as printed.
     */
    public function markPrinted(int $id): JsonResponse
    {
        $card = $this->cardRepository->findById($id);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], 404);
        }

        try {
            $card->markAsPrinted();
            $this->cardRepository->save($card);

            return response()->json([
                'success' => true,
                'data' => $this->transformCardToArray($card),
                'message' => 'تم تسجيل طباعة الكارت'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Mark card as encoded.
     */
    public function markEncoded(Request $request, int $id): JsonResponse
    {
        $card = $this->cardRepository->findById($id);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], 404);
        }

        // Accept encoded_data as array (can be empty)
        $request->validate([
            'encoded_data' => 'sometimes|array'
        ]);

        // Get encoded_data, default to empty array if not provided
        $encodedData = $request->input('encoded_data', []);

        // Ensure it's always an array
        if (!is_array($encodedData)) {
            $encodedData = [];
        }

        try {
            $card->markAsEncoded($encodedData);
            $this->cardRepository->save($card);

            return response()->json([
                'success' => true,
                'data' => $this->transformCardToArray($card),
                'message' => 'تم تسجيل تشفير الكارت'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Revoke a card.
     */
    public function revoke(int $id): JsonResponse
    {
        $card = $this->cardRepository->findById($id);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], 404);
        }

        try {
            $card->revoke();
            $this->cardRepository->save($card);

            return response()->json([
                'success' => true,
                'data' => $this->transformCardToArray($card),
                'message' => 'تم إلغاء الكارت'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get expiring cards.
     */
    public function expiring(Request $request): JsonResponse
    {
        $daysAhead = $request->integer('days', 30);
        $cards = $this->cardValidationService->findExpiringCards($daysAhead);
        $transformedCards = array_map([$this, 'transformCardToArray'], $cards);

        return response()->json([
            'success' => true,
            'data' => $transformedCards
        ]);
    }

    /**
     * Get cards not yet printed.
     */
    public function notPrinted(): JsonResponse
    {
        $cards = $this->cardRepository->findNotPrinted();
        $transformedCards = array_map([$this, 'transformCardToArray'], $cards);

        return response()->json([
            'success' => true,
            'data' => $transformedCards
        ]);
    }

    /**
     * Get cards not yet encoded.
     */
    public function notEncoded(): JsonResponse
    {
        $cards = $this->cardRepository->findNotEncoded();
        $transformedCards = array_map([$this, 'transformCardToArray'], $cards);

        return response()->json([
            'success' => true,
            'data' => $transformedCards
        ]);
    }

    /**
     * Get card by subscription.
     */
    public function getBySubscription(int $subscriptionId): JsonResponse
    {
        $card = $this->cardRepository->findBySubscriptionId($subscriptionId);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found for this subscription'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformCardToArray($card)
        ]);
    }

    /**
     * Get all cards for an officer.
     */
    public function getByOfficer(int $officerId): JsonResponse
    {
        $cards = $this->cardRepository->findByOfficerId($officerId);
        $transformedCards = array_map([$this, 'transformCardToArray'], $cards);

        return response()->json([
            'success' => true,
            'data' => $transformedCards
        ]);
    }

    /**
     * Find card by token.
     */
    public function findByToken(string $token): JsonResponse
    {
        $card = $this->cardRepository->findByToken($token);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found with this token'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformCardToArray($card)
        ]);
    }

    /**
     * Find card by token HEX.
     */
    public function findByTokenHex(string $tokenHex): JsonResponse
    {
        $card = $this->cardRepository->findByTokenHex($tokenHex);
        
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found with this token HEX'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformCardToArray($card)
        ]);
    }

    /**
     * Get replacement card fee.
     */
    public function getReplacementFee(): JsonResponse
    {
        // Try to get from settings first, fallback to config
        $fee = Setting::get('membership_cards.replacement_card_fee', null);
        
        if ($fee === null) {
            $fee = config('defaults.membership_cards.replacement_card_fee', 50.00);
            // Save to settings for future use
            Setting::set(
                'membership_cards.replacement_card_fee',
                $fee,
                'float',
                'membership_cards',
                'رسوم إصدار بطاقة بديلة (بطاقة مفقودة)'
            );
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'fee' => (float) $fee,
                'currency' => 'EGP'
            ]
        ]);
    }

    /**
     * Update replacement card fee.
     */
    public function updateReplacementFee(Request $request): JsonResponse
    {
        $request->validate([
            'fee' => 'required|numeric|min:0'
        ]);

        try {
            $fee = (float) $request->input('fee');
            
            // Save to settings table
            Setting::set(
                'membership_cards.replacement_card_fee',
                $fee,
                'float',
                'membership_cards',
                'رسوم إصدار بطاقة بديلة (بطاقة مفقودة)'
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'fee' => $fee,
                    'currency' => 'EGP'
                ],
                'message' => 'تم تحديث رسوم البطاقة البديلة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الرسوم: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Issue a replacement card for a lost card.
     */
    public function issueReplacement(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|integer|exists:mc_subscriptions,id',
            'card_uid' => 'required|string|max:20',
            'expiry_date' => 'required|date',
            'notes' => 'nullable|string',
            'serial_id' => 'nullable|string|max:32'
        ]);

        // Validate card UID format
        if (!$this->cardValidationService->validateCardUidFormat($request->card_uid)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid card UID format. Must be valid MIFARE UID (4, 7, or 10 bytes in hex)'
            ], 422);
        }

        // Check if subscription has an active card
        $existingCard = $this->cardRepository->findBySubscriptionId($request->subscription_id);
        if (!$existingCard || !$existingCard->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد بطاقة نشطة لهذا الاشتراك'
            ], 422);
        }

        try {
            // Get replacement fee from settings or config
            $fee = Setting::get('membership_cards.replacement_card_fee', config('defaults.membership_cards.replacement_card_fee', 50.00));
            
            // Create DTO with is_replacement flag
            // Use the existing card's expiry date for replacement cards
            $data = $request->all();
            $data['is_replacement'] = true;
            $data['expiry_date'] = $existingCard->getExpiryDate()->format('Y-m-d');
            $dto = CreateMembershipCardDTO::fromArray($data);
            $command = new CreateMembershipCardCommand($dto);
            $card = $this->createCardHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $this->transformCardToArray($card),
                'fee' => $fee,
                'message' => 'تم إصدار بطاقة بديلة بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get card serial ID from NFC reader device.
     */
    public function getCardSerialId(Request $request): JsonResponse
    {
        $request->validate([
            'serial_id' => 'nullable|string|max:32'
        ]);

        try {
            $serialId = $request->input('serial_id');
            $response = $this->cardWriterService->getSerialId($serialId);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Read card from NFC reader and get membership ID.
     */
    public function readCardAndGetMembershipId(Request $request): JsonResponse
    {
        try {
            // Call NFC reader agent to get card UID/payload
            $nfcResponse = $this->cardWriterService->getSerialId(null);
            
            if (!isset($nfcResponse['serial_id']) || empty($nfcResponse['serial_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم قراءة البطاقة. يرجى التأكد من وضع البطاقة على القارئ'
                ], 400);
            }

            $cardUid = $nfcResponse['serial_id'];
            
            // Validate the card and get card data
            $result = $this->cardValidationService->validateByCardUid($cardUid);
            
            if (!$result['valid'] || !$result['card']) {
                $errors = $result['errors'] ?? [];
                $errorMessage = !empty($errors) ? implode(', ', $errors) : 'البطاقة غير صالحة';
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422);
            }

            $card = $result['card'];
            
            // Check if card is active
            if (!$card->isActive() || $card->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'البطاقة غير نشطة أو منتهية الصلاحية'
                ], 422);
            }

            // Get subscription and officer data
            $subscription = $this->subscriptionRepository->findById($card->getSubscriptionId());
            
            if (!$subscription || !$subscription->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك المرتبط بالبطاقة غير نشط'
                ], 422);
            }

            $officer = $this->officerRepository->findById($subscription->getOfficerId());
            
            if (!$officer) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على بيانات الضابط'
                ], 404);
            }

            // Get military number
            $militaryNumber = $officer->getMilitaryNumber()->getValue();

            return response()->json([
                'success' => true,
                'data' => [
                    'membership_id' => $officer->getMembershipId(),
                    'military_number' => $militaryNumber,
                    'card_uid' => $cardUid,
                    'officer' => [
                        'id' => $officer->getId(),
                        'full_name' => $officer->getFullName(),
                        'rank' => $officer->getRank(),
                        'military_number' => $militaryNumber,
                        'membership_id' => $officer->getMembershipId(),
                        'is_staff_officer' => $officer->isStaffOfficer(),
                        'service_status' => $officer->getServiceStatus(),
                    ],
                    'card' => $this->transformCardToArray($card),
                ],
                'message' => 'تم قراءة البطاقة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Write data to NFC card via device.
     */
    public function writeCardData(Request $request): JsonResponse
    {
        $request->validate([
            'card_token' => 'required|string|max:32',
            'data' => 'required|string|size:32|regex:/^[0-9A-Fa-f]+$/i',
            'block' => 'nullable|integer|min:0|max:63'
        ]);

        try {
            $cardToken = $request->input('card_token');
            $dataHex = strtoupper($request->input('data'));
            $block = $request->input('block', 4);

            $response = $this->cardWriterService->writeCard($cardToken, $dataHex, $block);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'تم كتابة البيانات على البطاقة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform MembershipCard entity to array for JSON response.
     */
    private function transformCardToArray($card): array
    {
        // Fetch related entities
        $subscription = $this->subscriptionRepository->findById($card->getSubscriptionId());
        $officer = null;
        $beneficiary = null;
        $isForOfficer = false;

        if ($subscription) {
            $officer = $this->officerRepository->findById($subscription->getOfficerId());
            $isForOfficer = $subscription->isForOfficer();
            
            if (!$isForOfficer && $subscription->getBeneficiaryId()) {
                $beneficiary = $this->beneficiaryRepository->findById($subscription->getBeneficiaryId());
            }
        }

        // Get photo URL - only one photo: officer photo if for officer, beneficiary photo if for beneficiary
        $photoUrl = null;
        if ($isForOfficer && $officer && $officer->getPhoto()) {
            // Convert storage/ path to relative path for public disk
            $relativePath = str_replace('storage/', '', $officer->getPhoto());
            // Remove any leading slash to prevent double slashes in URL
            $relativePath = ltrim($relativePath, '/');
            // Storage::disk('public')->url() returns full URL already
            $photoUrl = Storage::disk('public')->url($relativePath);
        } elseif (!$isForOfficer && $beneficiary && $beneficiary->getPhoto()) {
            // Convert storage/ path to relative path for public disk
            $relativePath = str_replace('storage/', '', $beneficiary->getPhoto());
            // Remove any leading slash to prevent double slashes in URL
            $relativePath = ltrim($relativePath, '/');
            // Storage::disk('public')->url() returns full URL already
            $photoUrl = Storage::disk('public')->url($relativePath);
        }

        // Get subscription data
        $subscription = $this->subscriptionRepository->findById($card->getSubscriptionId());

        return [
            'id' => $card->getId(),
            'subscription_id' => $card->getSubscriptionId(),
            'card_uid' => $card->getCardUid()->getValue(),
            'card_uid_formatted' => $card->getCardUid()->getFormatted(),
            'printed_at' => $card->getPrintedAt()?->format('Y-m-d H:i:s'),
            'encoded_at' => $card->getEncodedAt()?->format('Y-m-d H:i:s'),
            'expiry_date' => $card->getExpiryDate()->format('Y-m-d'),
            'status' => $card->getStatus(),
            'is_active' => $card->isActive(),
            'is_expired' => $card->isExpired(),
            'is_revoked' => $card->isRevoked(),
            'is_printed' => $card->isPrinted(),
            'is_encoded' => $card->isEncoded(),
            'is_ready' => $card->isReady(),
            'days_until_expiry' => $card->getDaysUntilExpiry(),
            'notes' => $card->getNotes(),
            'card_token' => $card->getCardToken(),
            'card_token_hex' => $card->getCardTokenHex(),
            'serial_id' => $card->getSerialId(),
            'is_replacement' => $card->isReplacement(),
            'show_expiry_date' => $card->getShowExpiryDate(),
            // Photo - only one: officer photo if for officer, beneficiary photo if for beneficiary
            'photo' => $photoUrl,
            // Include related entities
            'is_for_officer' => $isForOfficer,
            'officer' => $officer ? [
                'id' => $officer->getId(),
                'full_name' => $officer->getFullName(),
                'rank' => $officer->getRank(),
                'military_number' => $officer->getMilitaryNumber()->getValue(),
                'membership_id' => $officer->getMembershipId(),
                'seniority_number' => $officer->getSeniorityNumber(),
                'national_id' => $officer->getNationalId(),
                'weapon_type' => $officer->getWeaponType(),
                'is_staff_officer' => $officer->isStaffOfficer(),
                'service_status' => $officer->getServiceStatus(),
            ] : null,
            'beneficiary' => $beneficiary ? [
                'id' => $beneficiary->getId(),
                'full_name' => $beneficiary->getFullName(),
                'relationship_type' => $beneficiary->getRelationshipType(),
                'national_id' => $beneficiary->getNationalId(),
                'birth_date' => $beneficiary->getBirthDate()?->format('Y-m-d'),
            ] : null,
            'subscription' => $subscription ? [
                'id' => $subscription->getId(),
                'is_honorary_membership' => $subscription->isHonoraryMembership(),
            ] : null,
        ];
    }
}

