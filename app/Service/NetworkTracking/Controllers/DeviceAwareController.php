<?php

namespace App\Service\NetworkTracking\Controllers;

use Illuminate\Http\Request;
use App\Service\NetworkTracking\Models\NetworkDevice;

class DeviceAwareController extends Controller
{
    public function handle(Request $request)
    {
        $device = $request->get('network_device');

        if ($device) {
            return response()->json([
                'success' => true,
                'device_id' => $device->device_id,
                'location' => $device->location,
                'mac' => $device->mac_address,
                'ip' => $device->ip_address,
                'name' => $device->name,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Device not recognized',
            'client_ip' => $request->ip()
        ], 404);
    }

    public function listDevices()
    {
        $devices = NetworkDevice::all();
        
        return response()->json([
            'success' => true,
            'devices' => $devices
        ]);
    }

    public function updateDevice(Request $request, $id)
    {
        $device = NetworkDevice::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $device->update($validated);

        return response()->json([
            'success' => true,
            'device' => $device
        ]);
    }
} 