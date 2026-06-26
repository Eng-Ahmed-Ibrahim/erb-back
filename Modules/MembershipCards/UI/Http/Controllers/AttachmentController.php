<?php

namespace Modules\MembershipCards\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\MembershipCards\Domain\Services\AttachmentService;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;

class AttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $attachmentService,
        private OfficerRepositoryInterface $officerRepository,
        private BeneficiaryRepositoryInterface $beneficiaryRepository
    ) {}

    /**
     * Get all attachments for an officer
     */
    public function getOfficerAttachments(int $officerId): JsonResponse
    {
        if (!$this->officerRepository->exists($officerId)) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        $attachments = $this->attachmentService->getOfficerAttachments($officerId);

        return response()->json([
            'success' => true,
            'data' => array_map([$this, 'transformAttachment'], $attachments)
        ]);
    }

    /**
     * Upload attachment for an officer
     */
    public function uploadForOfficer(Request $request, int $officerId): JsonResponse
    {
        if (!$this->officerRepository->exists($officerId)) {
            return response()->json([
                'success' => false,
                'message' => 'Officer not found'
            ], 404);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $file = $request->file('file');
            $description = $request->input('description');

            $attachment = $this->attachmentService->uploadForOfficer($officerId, $file, $description);

            return response()->json([
                'success' => true,
                'data' => $this->transformAttachment($attachment),
                'message' => 'تم رفع المرفق بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get all attachments for a beneficiary
     */
    public function getBeneficiaryAttachments(int $officerId, int $beneficiaryId): JsonResponse
    {
        $beneficiary = $this->beneficiaryRepository->findById($beneficiaryId);
        
        if (!$beneficiary || $beneficiary->getOfficerId() !== $officerId) {
            return response()->json([
                'success' => false,
                'message' => 'Beneficiary not found'
            ], 404);
        }

        $attachments = $this->attachmentService->getBeneficiaryAttachments($beneficiaryId);

        return response()->json([
            'success' => true,
            'data' => array_map([$this, 'transformAttachment'], $attachments)
        ]);
    }

    /**
     * Upload attachment for a beneficiary
     */
    public function uploadForBeneficiary(Request $request, int $officerId, int $beneficiaryId): JsonResponse
    {
        $beneficiary = $this->beneficiaryRepository->findById($beneficiaryId);
        
        if (!$beneficiary || $beneficiary->getOfficerId() !== $officerId) {
            return response()->json([
                'success' => false,
                'message' => 'Beneficiary not found'
            ], 404);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $file = $request->file('file');
            $description = $request->input('description');

            $attachment = $this->attachmentService->uploadForBeneficiary($officerId, $beneficiaryId, $file, $description);

            return response()->json([
                'success' => true,
                'data' => $this->transformAttachment($attachment),
                'message' => 'تم رفع المرفق بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get a single attachment
     */
    public function show(int $id): JsonResponse
    {
        $attachment = $this->attachmentService->getAttachment($id);

        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformAttachment($attachment)
        ]);
    }

    /**
     * Update attachment description
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $attachment = $this->attachmentService->getAttachment($id);

        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], 404);
        }

        $request->validate([
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $updatedAttachment = $this->attachmentService->updateDescription($id, $request->input('description'));

            return response()->json([
                'success' => true,
                'data' => $this->transformAttachment($updatedAttachment),
                'message' => 'تم تحديث المرفق بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete an attachment
     */
    public function destroy(int $id): JsonResponse
    {
        $attachment = $this->attachmentService->getAttachment($id);

        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], 404);
        }

        try {
            $this->attachmentService->deleteAttachment($id);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المرفق بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Transform attachment entity to array for JSON response
     */
    private function transformAttachment($attachment): array
    {
        return [
            'id' => $attachment->getId(),
            'original_name' => $attachment->getOriginalName(),
            'file_path' => $attachment->getFilePath(),
            'file_url' => $this->attachmentService->getAttachmentUrl($attachment),
            'mime_type' => $attachment->getMimeType(),
            'file_size' => $attachment->getFileSize(),
            'file_size_formatted' => $attachment->getFileSizeFormatted(),
            'description' => $attachment->getDescription(),
            'attachable_type' => $attachment->getAttachableType(),
            'attachable_id' => $attachment->getAttachableId(),
        ];
    }
}
