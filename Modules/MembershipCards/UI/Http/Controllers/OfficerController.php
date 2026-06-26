<?php

namespace Modules\MembershipCards\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\MembershipCards\Application\Commands\CreateOfficerCommand;
use Modules\MembershipCards\Application\DTOs\CreateOfficerDTO;
use Modules\MembershipCards\Application\Handlers\CreateOfficerHandler;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\MilitaryNumber;

class OfficerController extends Controller
{
    public function __construct(
        private OfficerRepositoryInterface $officerRepository,
        private CreateOfficerHandler $createOfficerHandler
    ) {}

    /**
     * Display a paginated listing of officers.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $perPage = $request->integer('per_page', 15);

        $result = $this->officerRepository->paginate($perPage, $search);

        $result['data'] = array_map([$this, 'transformOfficerToArray'], $result['data']);

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
     * Store a newly created officer.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'national_id' => 'required|string|size:14|unique:mc_officers,national_id',
            'full_name' => 'required|string|max:255',
            'rank' => 'required|string|max:100',
            'weapon_type' => 'required|string|in:infantry,other',
            'seniority_number' => 'nullable|string|max:50',
            'military_number' => 'nullable|string|max:50|unique:mc_officers,military_number',
            'membership_id' => 'nullable|string|max:50',
            'age' => 'nullable|integer|min:18|max:100',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'service_status' => 'nullable|string|in:active,retired,transferred,deceased,martyr,recalled',
            'is_staff_officer' => 'nullable|boolean'
        ]);

        try {
            $data = $request->all();
            $militaryNumber = $data['military_number'] ?? null;
            
            // Handle photo upload with new folder structure
            if ($request->hasFile('photo') && $militaryNumber) {
                $photo = $request->file('photo');
                $extension = $photo->getClientOriginalExtension();
                // Store in: membership-cards/officers/{military_number}/photo.{ext}
                $storagePath = "membership-cards/officers/{$militaryNumber}";
                $filename = "photo.{$extension}";
                $photo->storeAs("public/{$storagePath}", $filename);
                $data['photo'] = "storage/{$storagePath}/{$filename}";
            } else {
                // Remove photo from data if not provided
                unset($data['photo']);
            }
            
            $dto = CreateOfficerDTO::fromArray($data);
            $command = new CreateOfficerCommand($dto);
            $officer = $this->createOfficerHandler->handle($command);
            
            // Reload officer to get fresh data including photo
            $officer = $this->officerRepository->findById($officer->getId());

            return response()->json([
                'success' => true,
                'data' => $this->transformOfficerToArray($officer),
                'message' => 'تم تسجيل الضابط بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified officer.
     */
    public function show(int $id): JsonResponse
    {
        $officer = $this->officerRepository->findById($id);
        
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformOfficerToArray($officer)
        ]);
    }

    /**
     * Update the specified officer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $officer = $this->officerRepository->findById($id);
        
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        $request->validate([
            'national_id' => 'sometimes|string|size:14|unique:mc_officers,national_id,' . $id,
            'full_name' => 'sometimes|string|max:255',
            'rank' => 'sometimes|string|max:100',
            'weapon_type' => 'sometimes|string|in:infantry,other',
            'seniority_number' => 'nullable|string|max:50',
            'military_number' => 'sometimes|string|max:50|unique:mc_officers,military_number,' . $id,
            'membership_id' => 'nullable|string|max:50',
            'age' => 'nullable|integer|min:18|max:100',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'service_status' => 'nullable|string|in:active,retired,transferred,deceased,martyr,recalled',
            'is_staff_officer' => 'nullable|boolean'
        ]);

        try {
            $militaryNumber = $officer->getMilitaryNumber()->getValue();
            
            // Handle photo upload with new folder structure
            if ($request->hasFile('photo') && $militaryNumber) {
                // Delete old photo if exists
                $oldPhoto = $officer->getPhoto();
                if ($oldPhoto) {
                    $oldPhotoPath = str_replace('storage/', '', $oldPhoto);
                    if (Storage::disk('public')->exists($oldPhotoPath)) {
                        Storage::disk('public')->delete($oldPhotoPath);
                    }
                }
                
                $photo = $request->file('photo');
                $extension = $photo->getClientOriginalExtension();
                // Store in: membership-cards/officers/{military_number}/photo.{ext}
                $storagePath = "membership-cards/officers/{$militaryNumber}";
                $filename = "photo.{$extension}";
                $photo->storeAs("public/{$storagePath}", $filename);
                $officer->setPhoto("storage/{$storagePath}/{$filename}");
            }
            // If no photo is uploaded, keep existing photo (don't modify it)

            // Update other fields
            if ($request->has('national_id')) {
                $officer->setNationalId($request->national_id);
            }
            if ($request->has('full_name')) {
                $officer->setFullName($request->full_name);
            }
            if ($request->has('rank')) {
                $officer->setRank($request->rank);
            }
            if ($request->has('weapon_type')) {
                $officer->setWeaponType($request->weapon_type);
            }
            if ($request->has('seniority_number')) {
                $officer->setSeniorityNumber($request->seniority_number);
            }
            if ($request->has('military_number')) {
                $officer->setMilitaryNumber(new MilitaryNumber($request->military_number));
            }
            if ($request->has('membership_id')) {
                $officer->setMembershipId($request->membership_id);
            }
            if ($request->has('age')) {
                $officer->setAge($request->age);
            }
            if ($request->has('notes')) {
                $officer->setNotes($request->notes);
            }
            if ($request->has('service_status')) {
                $officer->setServiceStatus($request->service_status);
            }
            if ($request->has('is_staff_officer')) {
                $officer->setIsStaffOfficer($request->boolean('is_staff_officer'));
            }

            // Save the officer with photo (same as store - reload after save)
            $updatedOfficer = $this->officerRepository->save($officer);
            
            // Reload officer to get fresh data including photo (same as store method)
            $updatedOfficer = $this->officerRepository->findById($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformOfficerToArray($updatedOfficer),
                'message' => 'تم تحديث بيانات الضابط بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified officer.
     */
    public function destroy(int $id): JsonResponse
    {
        $officer = $this->officerRepository->findById($id);
        
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        $this->officerRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الضابط بنجاح'
        ]);
    }

    /**
     * Search officer by military number, membership ID, or national ID.
     */
    public function findByIdentifier(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        $identifier = $request->identifier;
        
        // Try to find by military number first
        $officer = $this->officerRepository->findByMilitaryNumber($identifier);
        
        // If not found, try membership ID
        if (!$officer) {
            $officer = $this->officerRepository->findByMembershipId($identifier);
        }

        // If not found, try national ID
        if (!$officer) {
            $officer = $this->officerRepository->findByNationalId($identifier);
        }
        
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformOfficerToArray($officer)
        ]);
    }

    /**
     * Transform Officer entity to array for JSON response.
     */
    private function transformOfficerToArray($officer): array
    {
        $photoUrl = null;
        if ($officer->getPhoto()) {
            // Convert storage/ path to relative path for public disk
            $relativePath = str_replace('storage/', '', $officer->getPhoto());
            $relativePath = ltrim($relativePath, '/');
            $photoUrl = Storage::disk('public')->url($relativePath);
        }

        return [
            'id' => $officer->getId(),
            'national_id' => $officer->getNationalId(),
            'full_name' => $officer->getFullName(),
            'rank' => $officer->getRank(),
            'weapon_type' => $officer->getWeaponType(),
            'seniority_number' => $officer->getSeniorityNumber(),
            'military_number' => $officer->getMilitaryNumber()->getValue(),
            'membership_id' => $officer->getMembershipId(),
            'age' => $officer->getAge(),
            'notes' => $officer->getNotes(),
            'photo' => $photoUrl,
            'service_status' => $officer->getServiceStatus(),
            'is_staff_officer' => $officer->isStaffOfficer(),
            'family_index' => $officer->getFamilyIndex(),
            'is_infantry' => $officer->isInfantry(),
        ];
    }
}
