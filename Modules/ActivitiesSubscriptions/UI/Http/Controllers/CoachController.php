<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ActivitiesSubscriptions\Application\Commands\CreateCoachCommand;
use Modules\ActivitiesSubscriptions\Application\DTOs\CreateCoachDTO;
use Modules\ActivitiesSubscriptions\Application\Handlers\CreateCoachHandler;
use Modules\ActivitiesSubscriptions\Domain\Repositories\CoachRepositoryInterface;

class CoachController extends Controller
{
    public function __construct(
        private CoachRepositoryInterface $coachRepository,
        private CreateCoachHandler $createCoachHandler
    ) {}

    /**
     * Display a listing of coaches for a specific academy.
     */
    public function index(Request $request): JsonResponse
    {
        $academyId = $request->query('academy_id');
        
        if ($academyId) {
            $coaches = $this->coachRepository->findByAcademyId($academyId);
        } else {
            $coaches = $this->coachRepository->findAll();
        }

        // Convert entities to arrays for JSON response
        $coachesArray = array_map(function($coach) {
            return [
                'id' => $coach->getId(),
                'academy_id' => $coach->getAcademyId(),
                'name' => $coach->getName(),
                'phone' => $coach->getPhone(),
                'bio' => $coach->getBio(),
                'active' => $coach->isActive(),
            ];
        }, $coaches);

        return response()->json([
            'success' => true,
            'data' => $coachesArray
        ]);
    }

    /**
     * Store a newly created coach.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'academy_id' => 'required|integer|exists:academies,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'active' => 'boolean'
        ]);

        try {
            $dto = CreateCoachDTO::fromArray($request->all());
            $command = new CreateCoachCommand($dto);
            $coach = $this->createCoachHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $coach->getId(),
                    'academy_id' => $coach->getAcademyId(),
                    'name' => $coach->getName(),
                    'phone' => $coach->getPhone(),
                    'bio' => $coach->getBio(),
                    'active' => $coach->isActive(),
                ],
                'message' => 'Coach created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified coach.
     */
    public function show(int $id): JsonResponse
    {
        $coach = $this->coachRepository->findById($id);

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Coach not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $coach->getId(),
                'academy_id' => $coach->getAcademyId(),
                'name' => $coach->getName(),
                'phone' => $coach->getPhone(),
                'bio' => $coach->getBio(),
                'active' => $coach->isActive(),
            ]
        ]);
    }

    /**
     * Update the specified coach.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $coach = $this->coachRepository->findById($id);

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Coach not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'active' => 'sometimes|boolean'
        ]);

        try {
            if ($request->has('name')) {
                $coach->setName($request->name);
            }
            if ($request->has('phone')) {
                $coach->setPhone($request->phone);
            }
            if ($request->has('bio')) {
                $coach->setBio($request->bio);
            }
            if ($request->has('active')) {
                $coach->setActive($request->active);
            }

            $updatedCoach = $this->coachRepository->save($coach);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $updatedCoach->getId(),
                    'academy_id' => $updatedCoach->getAcademyId(),
                    'name' => $updatedCoach->getName(),
                    'phone' => $updatedCoach->getPhone(),
                    'bio' => $updatedCoach->getBio(),
                    'active' => $updatedCoach->isActive(),
                ],
                'message' => 'Coach updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified coach.
     */
    public function destroy(int $id): JsonResponse
    {
        $coach = $this->coachRepository->findById($id);

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Coach not found'
            ], 404);
        }

        try {
            $this->coachRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Coach deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
