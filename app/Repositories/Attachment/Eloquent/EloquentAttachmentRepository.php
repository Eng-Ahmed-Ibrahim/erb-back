<?php

namespace App\Repositories\Attachment\Eloquent;

use App\Models\Attachment;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Attachment\AttachmentRepository;

class EloquentAttachmentRepository extends EloquentBaseRepository implements AttachmentRepository
{
    public function __construct()
    {
        parent::__construct(new Attachment);
    }

    public function adminCreate($data)
    {
        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        // Delete the actual file
        if ($model->file_path && file_exists(storage_path('app/' . $model->file_path))) {
            unlink(storage_path('app/' . $model->file_path));
        }

        return $this->delete($model);
    }

    public function getByBooking($bookingId)
    {
        return $this->model->where('booking_id', $bookingId)->get();
    }

    public function getByType($type)
    {
        return $this->model->where('type', $type)->with('booking')->get();
    }

    public function uploadFile($file, $bookingId, $type)
    {
        // Store file in the public disk under attachments directory
        $path = $file->store('attachments', 'public');

        // Create attachment record
        return $this->create([
            'booking_id' => $bookingId,
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}