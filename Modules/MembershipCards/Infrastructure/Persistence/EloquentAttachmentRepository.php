<?php

namespace Modules\MembershipCards\Infrastructure\Persistence;

use Modules\MembershipCards\Domain\Entities\Attachment;
use Modules\MembershipCards\Domain\Repositories\AttachmentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentAttachmentRepository implements AttachmentRepositoryInterface
{
    protected string $table = 'mc_attachments';

    public function findById(int $id): ?Attachment
    {
        $data = DB::table($this->table)->where('id', $id)->whereNull('deleted_at')->first();
        
        if (!$data) {
            return null;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function findByOfficerId(int $officerId): array
    {
        $data = DB::table($this->table)
            ->where('attachable_type', 'officer')
            ->where('attachable_id', $officerId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function findByBeneficiaryId(int $beneficiaryId): array
    {
        $data = DB::table($this->table)
            ->where('attachable_type', 'beneficiary')
            ->where('attachable_id', $beneficiaryId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $data->map(fn($item) => $this->mapToEntity($item))->toArray();
    }
    
    public function save(Attachment $attachment): Attachment
    {
        $data = [
            'attachable_type' => $attachment->getAttachableType(),
            'attachable_id' => $attachment->getAttachableId(),
            'original_name' => $attachment->getOriginalName(),
            'file_path' => $attachment->getFilePath(),
            'mime_type' => $attachment->getMimeType(),
            'file_size' => $attachment->getFileSize(),
            'description' => $attachment->getDescription(),
            'updated_at' => now(),
        ];
        
        if ($attachment->getId()) {
            DB::table($this->table)->where('id', $attachment->getId())->update($data);
            return $this->findById($attachment->getId());
        } else {
            $data['created_at'] = now();
            $id = DB::table($this->table)->insertGetId($data);
            $attachment->setId($id);
            return $this->findById($id);
        }
    }
    
    public function delete(int $id): void
    {
        DB::table($this->table)->where('id', $id)->update(['deleted_at' => now()]);
    }
    
    public function deleteByOfficerId(int $officerId): void
    {
        DB::table($this->table)
            ->where('attachable_type', 'officer')
            ->where('attachable_id', $officerId)
            ->update(['deleted_at' => now()]);
    }
    
    public function deleteBybBeneficiaryId(int $beneficiaryId): void
    {
        DB::table($this->table)
            ->where('attachable_type', 'beneficiary')
            ->where('attachable_id', $beneficiaryId)
            ->update(['deleted_at' => now()]);
    }
    
    private function mapToEntity($data): Attachment
    {
        $attachment = new Attachment(
            attachableType: $data->attachable_type,
            attachableId: $data->attachable_id,
            originalName: $data->original_name,
            filePath: $data->file_path,
            mimeType: $data->mime_type,
            fileSize: $data->file_size,
            description: $data->description
        );
        
        $attachment->setId($data->id);
        
        return $attachment;
    }
}
