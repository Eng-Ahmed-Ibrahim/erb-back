<?php

namespace App\Transformers\Attachment;

use App\Models\Attachment;
use App\Transformers\BaseTransformer;

class AbstractAttachmentTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Attachment $attachment)
    {
        return [
            'id' => (string) $attachment->id,
            'type' => $attachment->type,
            'original_name' => $attachment->original_name,
            'file_size' => $attachment->file_size,
            'file_size_human' => $attachment->file_size_human,
            'mime_type' => $attachment->mime_type,
            'file_url' => $attachment->file_url,
            'created_at' => $attachment->created_at->format('Y-m-d H:i:s'),
        ];
    }
}