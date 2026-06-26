<?php

namespace Modules\MembershipCards\Domain\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\MembershipCards\Domain\Entities\Attachment;
use Modules\MembershipCards\Domain\Repositories\AttachmentRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;

class AttachmentService
{
    private AttachmentRepositoryInterface $attachmentRepository;
    private OfficerRepositoryInterface $officerRepository;
    private BeneficiaryRepositoryInterface $beneficiaryRepository;
    private string $basePath = 'membership-cards';

    public function __construct(
        AttachmentRepositoryInterface $attachmentRepository,
        OfficerRepositoryInterface $officerRepository,
        BeneficiaryRepositoryInterface $beneficiaryRepository
    ) {
        $this->attachmentRepository = $attachmentRepository;
        $this->officerRepository = $officerRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
    }

    /**
     * Upload attachment for an officer
     * Storage structure: membership-cards/officers/{military_number}/attachments/
     */
    public function uploadForOfficer(int $officerId, UploadedFile $file, ?string $description = null): Attachment
    {
        $officer = $this->officerRepository->findById($officerId);
        if (!$officer) {
            throw new \InvalidArgumentException('Officer not found');
        }

        $militaryNumber = $officer->getMilitaryNumber()->getValue();
        $storagePath = $this->getOfficerAttachmentPath($militaryNumber);
        $filePath = $this->storeFile($file, $storagePath);

        $attachment = new Attachment(
            attachableType: 'officer',
            attachableId: $officerId,
            originalName: $file->getClientOriginalName(),
            filePath: $filePath,
            mimeType: $file->getMimeType(),
            fileSize: $file->getSize(),
            description: $description
        );

        return $this->attachmentRepository->save($attachment);
    }

    /**
     * Upload attachment for a beneficiary
     * Storage structure: membership-cards/officers/{military_number}/beneficiaries/{beneficiary_id}/attachments/
     */
    public function uploadForBeneficiary(int $officerId, int $beneficiaryId, UploadedFile $file, ?string $description = null): Attachment
    {
        $officer = $this->officerRepository->findById($officerId);
        if (!$officer) {
            throw new \InvalidArgumentException('Officer not found');
        }
        
        $beneficiary = $this->beneficiaryRepository->findById($beneficiaryId);
        if (!$beneficiary || $beneficiary->getOfficerId() !== $officerId) {
            throw new \InvalidArgumentException('Beneficiary not found');
        }

        $militaryNumber = $officer->getMilitaryNumber()->getValue();
        $storagePath = $this->getBeneficiaryAttachmentPath($militaryNumber, $beneficiaryId);
        $filePath = $this->storeFile($file, $storagePath);

        $attachment = new Attachment(
            attachableType: 'beneficiary',
            attachableId: $beneficiaryId,
            originalName: $file->getClientOriginalName(),
            filePath: $filePath,
            mimeType: $file->getMimeType(),
            fileSize: $file->getSize(),
            description: $description
        );

        return $this->attachmentRepository->save($attachment);
    }

    /**
     * Get all attachments for an officer
     */
    public function getOfficerAttachments(int $officerId): array
    {
        return $this->attachmentRepository->findByOfficerId($officerId);
    }

    /**
     * Get all attachments for a beneficiary
     */
    public function getBeneficiaryAttachments(int $beneficiaryId): array
    {
        return $this->attachmentRepository->findByBeneficiaryId($beneficiaryId);
    }

    /**
     * Get attachment by ID
     */
    public function getAttachment(int $id): ?Attachment
    {
        return $this->attachmentRepository->findById($id);
    }

    /**
     * Delete an attachment (soft delete in DB, remove file from storage)
     */
    public function deleteAttachment(int $id): bool
    {
        $attachment = $this->attachmentRepository->findById($id);
        if (!$attachment) {
            return false;
        }

        // Delete the file from storage
        $this->deleteFile($attachment->getFilePath());

        // Soft delete from database
        $this->attachmentRepository->delete($id);

        return true;
    }

    /**
     * Delete all attachments for an officer
     */
    public function deleteOfficerAttachments(int $officerId): void
    {
        $attachments = $this->attachmentRepository->findByOfficerId($officerId);
        
        foreach ($attachments as $attachment) {
            $this->deleteFile($attachment->getFilePath());
        }

        $this->attachmentRepository->deleteByOfficerId($officerId);
    }

    /**
     * Delete all attachments for a beneficiary
     */
    public function deleteBeneficiaryAttachments(int $beneficiaryId): void
    {
        $attachments = $this->attachmentRepository->findByBeneficiaryId($beneficiaryId);
        
        foreach ($attachments as $attachment) {
            $this->deleteFile($attachment->getFilePath());
        }

        $this->attachmentRepository->deleteBybBeneficiaryId($beneficiaryId);
    }

    /**
     * Get the public URL for an attachment
     */
    public function getAttachmentUrl(Attachment $attachment): string
    {
        $relativePath = str_replace('storage/', '', $attachment->getFilePath());
        return Storage::disk('public')->url($relativePath);
    }

    /**
     * Update attachment description
     */
    public function updateDescription(int $id, ?string $description): ?Attachment
    {
        $attachment = $this->attachmentRepository->findById($id);
        if (!$attachment) {
            return null;
        }

        $attachment->setDescription($description);
        return $this->attachmentRepository->save($attachment);
    }

    /**
     * Get the storage path for officer attachments
     * Path: membership-cards/officers/{military_number}/attachments
     */
    private function getOfficerAttachmentPath(string $militaryNumber): string
    {
        return "{$this->basePath}/officers/{$militaryNumber}/attachments";
    }

    /**
     * Get the storage path for beneficiary attachments
     * Path: membership-cards/officers/{military_number}/beneficiaries/{beneficiary_id}/attachments
     */
    private function getBeneficiaryAttachmentPath(string $militaryNumber, int $beneficiaryId): string
    {
        return "{$this->basePath}/officers/{$militaryNumber}/beneficiaries/{$beneficiaryId}/attachments";
    }

    /**
     * Store file to the public disk
     */
    private function storeFile(UploadedFile $file, string $path): string
    {
        // Generate unique filename while keeping original extension
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . uniqid() . '.' . $extension;
        
        // Store in public disk
        $file->storeAs("public/{$path}", $filename);
        
        // Return path in storage/ format for consistency with other files in the system
        return "storage/{$path}/{$filename}";
    }

    /**
     * Delete file from storage
     */
    private function deleteFile(string $filePath): void
    {
        // Convert storage/ path back to public/ for deletion
        $relativePath = str_replace('storage/', '', $filePath);
        
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    /**
     * Check if file exists
     */
    public function fileExists(Attachment $attachment): bool
    {
        $relativePath = str_replace('storage/', '', $attachment->getFilePath());
        return Storage::disk('public')->exists($relativePath);
    }

    /**
     * Get file contents
     */
    public function getFileContents(Attachment $attachment): ?string
    {
        $relativePath = str_replace('storage/', '', $attachment->getFilePath());
        
        if (!Storage::disk('public')->exists($relativePath)) {
            return null;
        }
        
        return Storage::disk('public')->get($relativePath);
    }
}
