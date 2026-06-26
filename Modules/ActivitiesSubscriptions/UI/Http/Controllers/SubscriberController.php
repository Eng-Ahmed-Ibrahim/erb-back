<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\ActivitiesSubscriptions\Application\Commands\CreateSubscriberCommand;
use Modules\ActivitiesSubscriptions\Application\DTOs\CreateSubscriberDTO;
use Modules\ActivitiesSubscriptions\Application\Handlers\CreateSubscriberHandler;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriberRepositoryInterface;

class SubscriberController extends Controller
{
    public function __construct(
        private SubscriberRepositoryInterface $subscriberRepository,
        private CreateSubscriberHandler $createSubscriberHandler
    ) {}

    /**
     * Display a listing of subscribers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->get('search');
        
        if ($query) {
            $subscribers = $this->subscriberRepository->search($query);
        } else {
            $subscribers = $this->subscriberRepository->findAll();
        }

        // Transform entities to arrays
        $transformedSubscribers = array_map([$this, 'transformSubscriberToArray'], $subscribers);

        return response()->json([
            'success' => true,
            'data' => $transformedSubscribers
        ]);
    }

    /**
     * Store a newly created subscriber.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'type' => 'required|string|in:infantry,civilian,other',
            'national_id' => 'nullable|string|unique:subscribers,national_id',
            'military_id' => 'nullable|string|unique:subscribers,military_id',
            'phone' => 'nullable|string|max:20'
        ]);

        try {
            $dto = CreateSubscriberDTO::fromArray($request->all());
            $command = new CreateSubscriberCommand($dto);
            $subscriber = $this->createSubscriberHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $this->transformSubscriberToArray($subscriber),
                'message' => 'Subscriber created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified subscriber.
     */
    public function show(int $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->findById($id);
        
        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSubscriberToArray($subscriber)
        ]);
    }

    /**
     * Update the specified subscriber.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->findById($id);
        
        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found'
            ], 404);
        }

        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:infantry,civilian,other',
            'national_id' => 'nullable|string|unique:subscribers,national_id,' . $id,
            'military_id' => 'nullable|string|unique:subscribers,military_id,' . $id,
            'phone' => 'nullable|string|max:20'
        ]);

        try {
            // Update subscriber properties
            if ($request->has('full_name')) {
                $subscriber->setFullName($request->full_name);
            }
            if ($request->has('type')) {
                $subscriber->setType($request->type);
            }
            if ($request->has('national_id')) {
                $subscriber->setNationalId($request->national_id);
            }
            if ($request->has('military_id')) {
                $subscriber->setMilitaryId($request->military_id);
            }
            if ($request->has('phone')) {
                $subscriber->setPhone($request->phone);
            }

            $updatedSubscriber = $this->subscriberRepository->save($subscriber);

            return response()->json([
                'success' => true,
                'data' => $this->transformSubscriberToArray($updatedSubscriber),
                'message' => 'Subscriber updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified subscriber.
     */
    public function destroy(int $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->findById($id);
        
        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found'
            ], 404);
        }

        $this->subscriberRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Subscriber deleted successfully'
        ]);
    }

    /**
     * Search subscribers by identifier.
     */
    public function searchByIdentifier(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        $subscriber = $this->subscriberRepository->findByUniqueIdentifier($request->identifier);
        
        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSubscriberToArray($subscriber)
        ]);
    }

    /**
     * Transform Subscriber entity to array for JSON response.
     */
    private function transformSubscriberToArray($subscriber): array
    {
        return [
            'id' => $subscriber->getId(),
            'full_name' => $subscriber->getFullName(),
            'type' => $subscriber->getType(),
            'national_id' => $subscriber->getNationalId(),
            'military_id' => $subscriber->getMilitaryId(),
            'phone' => $subscriber->getPhone(),
            'identifier' => $subscriber->getUniqueIdentifier(),
        ];
    }
}
