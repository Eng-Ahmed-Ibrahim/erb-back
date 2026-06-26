<?php

namespace App\Service\NetworkTracking\Transformers;

use App\Service\NetworkTracking\Models\NetworkDevice;
use App\Transformers\BaseTransformer;

class NetworkDeviceTransformer extends BaseTransformer
{
    public function transform(NetworkDevice $device): array
    {
        return [
            'id' => $device->id,
            'name' => $device->name,
            'mac_address' => $device->mac_address,
            'ip_address' => $device->ip_address,
            'device_id' => $device->device_id,
            'location' => $device->location,
            'created_at' => $device->created_at?->toISOString(),
            'updated_at' => $device->updated_at?->toISOString(),
        ];
    }
} 