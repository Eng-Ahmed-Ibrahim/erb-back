<?php

namespace App\Service\NetworkTracking\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Service\NetworkTracking\Repositories\NetworkDeviceRepository;

class AttachDeviceFromArp
{
    protected $repository;

    public function __construct(NetworkDeviceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        logger()->info("Checking device by IP: $ip");
        
        $device = $this->repository->findByIpAddress($ip);

        if ($device) {
            logger()->info("Device found for IP $ip: MAC={$device->mac_address}");
            $request->merge(['network_device' => $device]);
        } else {
            logger()->warning("No device found for IP: $ip");
        }

        return $next($request);
    }
} 