<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'id',
        'booking_id',
        'type',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
    ];

    // Attachment type constants
    const TYPE_DOCUMENT = 'document';
    const TYPE_PERMIT = 'permit';
    const TYPE_SIGNATURE = 'signature';

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function getFileUrlAttribute()
    {
        return config('app.url') . '/storage/' . str_replace('public/', '', $this->file_path);
    }

    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}