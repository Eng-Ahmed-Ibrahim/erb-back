<?php

namespace App\Service\NetworkTracking\Repositories;

use App\Service\NetworkTracking\Models\NetworkDevice;
use App\Repositories\EloquentBaseRepository;

class NetworkDeviceRepository extends EloquentBaseRepository
{
    public function __construct(NetworkDevice $model)
    {
        parent::__construct($model);
    }

    public function findByMacAddress(string $macAddress): ?NetworkDevice
    {
        return $this->model->where('mac_address', strtolower($macAddress))->first();
    }

    public function findByIpAddress(string $ipAddress): ?NetworkDevice
    {
        return $this->model->where('ip_address', $ipAddress)->first();
    }

    public function upsertByMacOrCreate(string $macAddress, array $data): NetworkDevice
    {
        $device = NetworkDevice::where('mac_address', $macAddress)->first();

        if ($device) {
            $device->update(['ip_address' => $data['ip_address']]);
        } else {
            $device =  NetworkDevice::create($data);
        }
        return $device;
    }

    public function getAllDevices()
    {
        return $this->model->all();
    }
} 