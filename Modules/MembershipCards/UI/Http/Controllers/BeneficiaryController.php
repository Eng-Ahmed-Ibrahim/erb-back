<?php

namespace Modules\MembershipCards\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\MembershipCards\Application\Commands\CreateBeneficiaryCommand;
use Modules\MembershipCards\Application\DTOs\CreateBeneficiaryDTO;
use Modules\MembershipCards\Application\Handlers\CreateBeneficiaryHandler;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;

class BeneficiaryController extends Controller
{
    public function __construct(
        private BeneficiaryRepositoryInterface $beneficiaryRepository,
        private OfficerRepositoryInterface $officerRepository,
        private CreateBeneficiaryHandler $createBeneficiaryHandler
    ) {}

    /**
     * Display a paginated listing of all beneficiaries (global).
     * When officer_id is provided as route param, returns beneficiaries for that officer.
     */
    public function index(Request $request, ?int $officerId = null): JsonResponse
    {
        // If officer_id is provided (nested route), return beneficiaries for that officer
        if ($officerId !== null) {
            if (!$this->officerRepository->exists($officerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Officer not found'
                ], 404);
            }

            $beneficiaries = $this->beneficiaryRepository->findByOfficerId($officerId);

            // Get officer's military number for photo URLs
            $officer = $this->officerRepository->findById($officerId);
            $militaryNumber = $officer ? $officer->getMilitaryNumber()->getValue() : null;

            $transformedBeneficiaries = array_map(
                fn($b) => $this->transformBeneficiaryToArray($b, $militaryNumber),
                $beneficiaries
            );

            return response()->json([
                'success' => true,
                'data' => $transformedBeneficiaries
            ]);
        }

        // Global paginated listing
        $search = $request->get('search');
        $perPage = $request->integer('per_page', 15);

        $result = $this->beneficiaryRepository->paginate($perPage, $search);

        $result['data'] = array_map(
            fn($b) => $this->transformBeneficiaryToArray($b),
            $result['data']
        );

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
     * Store a newly created beneficiary.
     */
    public function store(Request $request, int $officerId): JsonResponse
    {
        $officer = $this->officerRepository->findById($officerId);
        
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'relationship_type' => 'required|string|in:spouse,child,parent,grandchild,child_spouse,brother,sister,sister_spouse,over_age',
            'birth_date' => 'nullable|date',
            'national_id' => 'nullable|string|size:14',
            'family_index' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
        ]);

        try {
            $data = $request->all();
            $data['officer_id'] = $officerId;
            $militaryNumber = $officer->getMilitaryNumber()->getValue();
            
            // First create the beneficiary to get the ID
            // Remove photo temporarily
            $photoFile = $request->file('photo');
            unset($data['photo']);
            
            $dto = CreateBeneficiaryDTO::fromArray($data);
            $command = new CreateBeneficiaryCommand($dto);
            $beneficiary = $this->createBeneficiaryHandler->handle($command);
            
            // Now upload the photo with the beneficiary ID
            if ($photoFile && $militaryNumber) {
                $extension = $photoFile->getClientOriginalExtension();
                // Store in: membership-cards/officers/{military_number}/beneficiaries/{beneficiary_id}/photo.{ext}
                $storagePath = "membership-cards/officers/{$militaryNumber}/beneficiaries/{$beneficiary->getId()}";
                $filename = "photo.{$extension}";
                $photoFile->storeAs("public/{$storagePath}", $filename);
                
                // Update beneficiary with photo path
                $beneficiary->setPhoto("storage/{$storagePath}/{$filename}");
                $this->beneficiaryRepository->save($beneficiary);
            }

            // Reload beneficiary to get fresh data including photo
            $beneficiary = $this->beneficiaryRepository->findById($beneficiary->getId());

            return response()->json([
                'success' => true,
                'data' => $this->transformBeneficiaryToArray($beneficiary, $militaryNumber),
                'message' => 'تم إضافة المستفيد بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified beneficiary.
     */
    public function show(int $officerId, int $id): JsonResponse
    {
        $beneficiary = $this->beneficiaryRepository->findById($id);
        
        if (!$beneficiary || $beneficiary->getOfficerId() !== $officerId) {
            return response()->json([
                'success' => false,
                'message' => 'Beneficiary not found'
            ], 404);
        }
        
        // Get officer's military number for photo URL
        $officer = $this->officerRepository->findById($officerId);
        $militaryNumber = $officer ? $officer->getMilitaryNumber()->getValue() : null;

        return response()->json([
            'success' => true,
            'data' => $this->transformBeneficiaryToArray($beneficiary, $militaryNumber)
        ]);
    }

    /**
     * Update the specified beneficiary.
     */
    public function update(Request $request, int $officerId, int $id): JsonResponse
    {
        $beneficiary = $this->beneficiaryRepository->findById($id);
        
        if (!$beneficiary || $beneficiary->getOfficerId() !== $officerId) {
            return response()->json([
                'success' => false,
                'message' => 'Beneficiary not found'
            ], 404);
        }
        
        $officer = $this->officerRepository->findById($officerId);
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'relationship_type' => 'sometimes|string|in:spouse,child,parent,grandchild,child_spouse,brother,sister,sister_spouse,over_age',
            'birth_date' => 'nullable|date',
            'national_id' => 'nullable|string|size:14',
            'family_index' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
        ]);

        try {
            $militaryNumber = $officer->getMilitaryNumber()->getValue();
            
            // Handle photo upload with new folder structure
            if ($request->hasFile('photo') && $militaryNumber) {
                // Delete old photo if exists
                $oldPhoto = $beneficiary->getPhoto();
                if ($oldPhoto) {
                    $oldPhotoPath = str_replace('storage/', '', $oldPhoto);
                    if (Storage::disk('public')->exists($oldPhotoPath)) {
                        Storage::disk('public')->delete($oldPhotoPath);
                    }
                }
                
                $photo = $request->file('photo');
                $extension = $photo->getClientOriginalExtension();
                // Store in: membership-cards/officers/{military_number}/beneficiaries/{beneficiary_id}/photo.{ext}
                $storagePath = "membership-cards/officers/{$militaryNumber}/beneficiaries/{$id}";
                $filename = "photo.{$extension}";
                $photo->storeAs("public/{$storagePath}", $filename);
                $beneficiary->setPhoto("storage/{$storagePath}/{$filename}");
            }
            // If no photo is uploaded, keep existing photo (don't modify it)

            // Update other fields
            if ($request->has('full_name')) {
                $beneficiary->setFullName($request->full_name);
            }
            if ($request->has('relationship_type')) {
                $beneficiary->setRelationshipType($request->relationship_type);
            }
            if ($request->has('birth_date')) {
                $beneficiary->setBirthDate($request->birth_date ? \Carbon\Carbon::parse($request->birth_date) : null);
            }
            if ($request->has('national_id')) {
                $beneficiary->setNationalId($request->national_id);
            }
            if ($request->has('family_index')) {
                $beneficiary->setFamilyIndex((int) $request->family_index);
            }
            if ($request->has('notes')) {
                $beneficiary->setNotes($request->notes);
            }

            // Save the beneficiary with photo (same as store - reload after save)
            $updatedBeneficiary = $this->beneficiaryRepository->save($beneficiary);

            // Reload beneficiary to get fresh data including photo (same as store method)
            $updatedBeneficiary = $this->beneficiaryRepository->findById($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformBeneficiaryToArray($updatedBeneficiary, $militaryNumber),
                'message' => 'تم تحديث بيانات المستفيد بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified beneficiary.
     */
    public function destroy(int $officerId, int $id): JsonResponse
    {
        $beneficiary = $this->beneficiaryRepository->findById($id);
        
        if (!$beneficiary || $beneficiary->getOfficerId() !== $officerId) {
            return response()->json([
                'success' => false,
                'message' => 'Beneficiary not found'
            ], 404);
        }

        $this->beneficiaryRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستفيد بنجاح'
        ]);
    }

    /**
     * Transform Beneficiary entity to array for JSON response.
     */
    private function transformBeneficiaryToArray($beneficiary, ?string $militaryNumber = null): array
    {
        $photoUrl = null;
        if ($beneficiary->getPhoto()) {
            // Convert storage/ path to relative path for public disk
            $relativePath = str_replace('storage/', '', $beneficiary->getPhoto());
            $relativePath = ltrim($relativePath, '/');
            $photoUrl = Storage::disk('public')->url($relativePath);
        }

        return [
            'id' => $beneficiary->getId(),
            'officer_id' => $beneficiary->getOfficerId(),
            'full_name' => $beneficiary->getFullName(),
            'relationship_type' => $beneficiary->getRelationshipType(),
            'birth_date' => $beneficiary->getBirthDate()?->format('Y-m-d'),
            'national_id' => $beneficiary->getNationalId(),
            'family_index' => $beneficiary->getFamilyIndex(),
            'age' => $beneficiary->getAge(),
            'notes' => $beneficiary->getNotes(),
            'photo' => $photoUrl,
        ];
    }
}
