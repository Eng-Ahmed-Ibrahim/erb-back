<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\ActivitiesSubscriptions\Application\Commands\CreateAcademyCommand;
use Modules\ActivitiesSubscriptions\Application\DTOs\CreateAcademyDTO;
use Modules\ActivitiesSubscriptions\Application\Handlers\CreateAcademyHandler;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\CoachRepositoryInterface;

class AcademyController extends Controller
{
    public function __construct(
        private AcademyRepositoryInterface $academyRepository,
        private CreateAcademyHandler $createAcademyHandler,
        private CoachRepositoryInterface $coachRepository
    ) {}

    /** 
     * Display a listing of academies.
     */
    public function index(): JsonResponse
    {
        $academies = $this->academyRepository->findAll();

        // Convert entities to arrays for JSON response
        $academiesArray = array_map(function ($academy) {
            // Get coaches count for this academy
            $coachesCount = count($this->coachRepository->findByAcademyId($academy->getId()));

            return [
                'id' => $academy->getId(),
                'name' => $academy->getName(),
                'contracted' => $academy->isContracted(),
                'revenue_share_infantry' => $academy->getRevenueShareInfantry()->getValue(),
                'revenue_share_academy' => $academy->getRevenueShareAcademy()->getValue(),
                'working_days' => $academy->getWorkingDays(),
                'status' => $academy->getStatus(),
                'coaches_count' => $coachesCount,
            ];
        }, $academies);

        return response()->json([
            'success' => true,
            'data' => $academiesArray
        ]);
    }

    /**
     * Store a newly created academy.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contracted' => 'boolean',
            'revenue_share_infantry' => 'required|numeric|min:0|max:100',
            'revenue_share_academy' => 'required|numeric|min:0|max:100',
            'working_days' => 'required|array',
            'working_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'status' => 'string|in:active,inactive'
        ]);

        // Validate revenue share totals 100%
        if ($request->revenue_share_infantry + $request->revenue_share_academy !== 100) {
            return response()->json([
                'success' => false,
                'message' => 'Revenue share percentages must total 100%'
            ], 422);
        }

        try {
            $dto = CreateAcademyDTO::fromArray($request->all());
            $command = new CreateAcademyCommand($dto);
            $academy = $this->createAcademyHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $academy->getId(),
                    'name' => $academy->getName(),
                    'contracted' => $academy->isContracted(),
                    'revenue_share_infantry' => $academy->getRevenueShareInfantry()->getValue(),
                    'revenue_share_academy' => $academy->getRevenueShareAcademy()->getValue(),
                    'working_days' => $academy->getWorkingDays(),
                    'status' => $academy->getStatus(),
                ],
                'message' => 'Academy created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified academy.
     */
    public function show(int $id): JsonResponse
    {
        $academy = $this->academyRepository->findById($id);
        
        if (!$academy) {
            return response()->json([
                'success' => false,
                'message' => 'Academy not found'
            ], 404);
        }

        // Get coaches count for this academy
        $coachesCount = count($this->coachRepository->findByAcademyId($academy->getId()));

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $academy->getId(),
                'name' => $academy->getName(),
                'contracted' => $academy->isContracted(),
                'revenue_share_infantry' => $academy->getRevenueShareInfantry()->getValue(),
                'revenue_share_academy' => $academy->getRevenueShareAcademy()->getValue(),
                'working_days' => $academy->getWorkingDays(),
                'status' => $academy->getStatus(),
                'coaches_count' => $coachesCount,
            ]
        ]);
    }

    /**
     * Update the specified academy.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $academy = $this->academyRepository->findById($id);
        
        if (!$academy) {
            return response()->json([
                'success' => false,
                'message' => 'Academy not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'contracted' => 'sometimes|boolean',
            'revenue_share_infantry' => 'sometimes|numeric|min:0|max:100',
            'revenue_share_academy' => 'sometimes|numeric|min:0|max:100',
            'working_days' => 'sometimes|array',
            'working_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'status' => 'sometimes|string|in:active,inactive'
        ]);

        try {
            // Update academy properties
            if ($request->has('name')) {
                $academy->setName($request->name);
            }
            if ($request->has('contracted')) {
                $academy->setContracted($request->contracted);
            }
            if ($request->has('revenue_share_infantry') && $request->has('revenue_share_academy')) {
                $academy->setRevenueShareInfantry(new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\Percentage($request->revenue_share_infantry));
                $academy->setRevenueShareAcademy(new \Modules\ActivitiesSubscriptions\Domain\ValueObjects\Percentage($request->revenue_share_academy));
            }
            if ($request->has('working_days')) {
                $academy->setWorkingDays($request->working_days);
            }
            if ($request->has('status')) {
                $academy->setStatus($request->status);
            }

            $updatedAcademy = $this->academyRepository->save($academy);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $updatedAcademy->getId(),
                    'name' => $updatedAcademy->getName(),
                    'contracted' => $updatedAcademy->isContracted(),
                    'revenue_share_infantry' => $updatedAcademy->getRevenueShareInfantry()->getValue(),
                    'revenue_share_academy' => $updatedAcademy->getRevenueShareAcademy()->getValue(),
                    'working_days' => $updatedAcademy->getWorkingDays(),
                    'status' => $updatedAcademy->getStatus(),
                ],
                'message' => 'Academy updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified academy.
     */
    public function destroy(int $id): JsonResponse
    {
        $academy = $this->academyRepository->findById($id);
        
        if (!$academy) {
            return response()->json([
                'success' => false,
                'message' => 'Academy not found'
            ], 404);
        }

        $this->academyRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Academy deleted successfully'
        ]);
    }
}
