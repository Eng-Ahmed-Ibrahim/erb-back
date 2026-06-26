<?php

namespace Modules\MembershipCards\Domain\Repositories;

use Modules\MembershipCards\Domain\Entities\Attachment;

interface AttachmentRepositoryInterface
{
    public function findById(int $id): ?Attachment;
    
    public function findByOfficerId(int $officerId): array;
    
    public function findByBeneficiaryId(int $beneficiaryId): array;
    
    public function save(Attachment $attachment): Attachment;
    
    public function delete(int $id): void;
    
    public function deleteByOfficerId(int $officerId): void;
    
    public function deleteBybBeneficiaryId(int $beneficiaryId): void;
}
