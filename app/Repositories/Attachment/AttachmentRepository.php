<?php

namespace App\Repositories\Attachment;

use App\Repositories\BaseRepository;

interface AttachmentRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function getByBooking($bookingId);

    public function getByType($type);

    public function uploadFile($file, $bookingId, $type);
}