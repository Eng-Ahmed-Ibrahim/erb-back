<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\ActivitiesSubscriptions\Application\Commands\CreateOfferCommand;
use Modules\ActivitiesSubscriptions\Application\DTOs\CreateOfferDTO;
use Modules\ActivitiesSubscriptions\Application\Handlers\CreateOfferHandler;
use Modules\ActivitiesSubscriptions\Domain\Repositories\OfferRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Price;
class OfferController extends Controller
{
    public function __construct(
        private OfferRepositoryInterface $offerRepository,
        private CreateOfferHandler $createOfferHandler,
        private AcademyRepositoryInterface $academyRepository
    ) {}

    /**
     * Display a listing of offers.
     */
    public function index(): JsonResponse
    {
        $offers = $this->offerRepository->findAll();
        
        $transformedOffers = array_map(function ($offer) {
            return $this->transformOfferToArray($offer);
        }, $offers);
        
        return response()->json([
            'success' => true,
            'data' => $transformedOffers
        ]);
    }

    /**
     * Store a newly created offer.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'academy_id' => 'required|integer|exists:academies,id',
            'name' => 'required|string|max:255',
            'num_classes' => 'nullable|integer|min:1',
            'num_hours' => 'nullable|integer|min:1',
            'duration_days' => 'required|integer|min:1',
            'available_days' => 'required|array',
            'available_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'price_infantry' => 'required|numeric|min:0',
            'price_civilian' => 'required|numeric|min:0',
            'price_other' => 'required|numeric|min:0',
            'active' => 'boolean'
        ]);

        // Validate that either num_classes or num_hours is provided, but not both
        if ($request->num_classes === null && $request->num_hours === null)  {
            return response()->json([
                'success' => false,
                'message' => 'Offer must specify either number of classes or hours'
            ], 422);
        }

        // Validate that offer available days are subset of academy working days
        $academy = $this->academyRepository->findById($request->academy_id);
        if (!$academy) {
            return response()->json([
                'success' => false,
                'message' => 'Academy not found'
            ], 422);
        }

        $invalidDays = array_diff($request->available_days, $academy->getWorkingDays());
        if (!empty($invalidDays)) {
            return response()->json([
                'success' => false,
                'message' => 'Offer available days must be subset of academy working days. Invalid days: ' . implode(', ', $invalidDays)
            ], 422);
        }

        try {

            $dto = CreateOfferDTO::fromArray($request->all());
            $command = new CreateOfferCommand($dto);
            $offer = $this->createOfferHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $this->transformOfferToArray($offer),
                'message' => 'Offer created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified offer.
     */
    public function show(int $id): JsonResponse
    {
        $offer = $this->offerRepository->findById($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformOfferToArray($offer)
        ]);
    }

    /**
     * Update the specified offer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $offer = $this->offerRepository->findById($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'num_classes' => 'nullable|integer|min:1',
            'num_hours' => 'nullable|integer|min:1',
            'duration_days' => 'sometimes|integer|min:1',
            'available_days' => 'sometimes|array',
            'available_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'price_infantry' => 'required|numeric|min:0',
            'price_civilian' => 'required|numeric|min:0',
            'price_other' => 'required|numeric|min:0',
            'active' => 'sometimes|boolean'
        ]);
        Log::info('dtooooooooooo',[$request->all()]);

        // Validate that offer available days are subset of academy working days (if updating available days)
        if ($request->has('available_days')) {
            $academy = $this->academyRepository->findById($offer->getAcademyId());
            if ($academy) {
                $invalidDays = array_diff($request->available_days, $academy->getWorkingDays());
                if (!empty($invalidDays)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Offer available days must be subset of academy working days. Invalid days: ' . implode(', ', $invalidDays)
                    ], 422);
                }
            }
        }

        try {
            // Update offer properties
            if ($request->has('name')) {
                $offer->setName($request->name);
            }
            if ($request->has('duration_days')) {
                $offer->setDurationDays($request->duration_days);
            }
            if ($request->has('available_days')) {
                $offer->setAvailableDays($request->available_days);
            }
            
            if ($request->has('price_civilian')) {
                $offer->setPriceCivilian(new Price((float)$request->price_civilian));
            }
            if ($request->has('price_infantry')) {
                $offer->setPriceInfantry(new Price((float)$request->price_infantry));
            }
            
            if ($request->has('price_other')) {
                $offer->setPriceOther(new Price((float)$request->price_other));
            }


            if ($request->has('active')) {
                $offer->setActive($request->active);
            }

            if ($request->has('num_classes')) {
                $offer->setNumClasses($request->num_classes);
            }

            if ($request->has('num_hours')) {
                $offer->setNumHours($request->num_hours);
            } 
            
            if ($request->has('duration_days')) {
                $offer->setDurationDays($request->duration_days);
            }

            $updatedOffer = $this->offerRepository->save($offer);

            return response()->json([
                'success' => true,
                'data' => $this->transformOfferToArray($updatedOffer),
                'message' => 'Offer updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified offer.
     */
    public function destroy(int $id): JsonResponse
    {
        $offer = $this->offerRepository->findById($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }

        $this->offerRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Offer deleted successfully'
        ]);
    }

    /**
     * Get offers by academy.
     */
    public function getByAcademy(int $academyId): JsonResponse
    {
        $offers = $this->offerRepository->findByAcademyId($academyId);
        
        $transformedOffers = array_map(function ($offer) {
            return $this->transformOfferToArray($offer);
        }, $offers);
        
        return response()->json([
            'success' => true,
            'data' => $transformedOffers
        ]);
    }

    /**
     * Transform Offer entity to array for JSON response.
     */
    private function transformOfferToArray($offer): array
    {
        return [
            'id' => $offer->getId(),
            'academy_id' => $offer->getAcademyId(),
            'name' => $offer->getName(),
            'num_classes' => $offer->getNumClasses(),
            'num_hours' => $offer->getNumHours(),
            'duration_days' => $offer->getDurationDays(),
            'available_days' => $offer->getAvailableDays(),
            'price_infantry' => $offer->getPriceInfantry()->getAmount(),
            'price_civilian' => $offer->getPriceCivilian()->getAmount(),
            'price_other' => $offer->getPriceOther()->getAmount(),
            'active' => $offer->isActive(),
        ];
    }
}
