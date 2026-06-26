<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Service\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings or specific group
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');

        if ($group) {
            $settings = Setting::where('group', $group)->get();
        } else {
            $settings = Setting::all();
        }

        // Transform to key-value pairs
        $transformedSettings = $settings->mapWithKeys(function ($setting) {
            return [
                $setting->key => [
                    'value' => Setting::get($setting->key),
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                ]
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $transformedSettings,
        ]);
    }

    /**
     * Get a specific setting by key
     * 
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'key' => $setting->key,
                'value' => Setting::get($key),
                'type' => $setting->type,
                'group' => $setting->group,
                'description' => $setting->description,
            ],
        ]);
    }

    /**
     * Update a setting
     * 
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        Setting::set(
            $key,
            $request->value,
            $setting->type,
            $setting->group,
            $setting->description
        );

        return response()->json([
            'status' => true,
            'message' => 'Setting updated successfully',
            'data' => [
                'key' => $key,
                'value' => Setting::get($key),
            ],
        ]);
    }

    /**
     * Get inventory settings
     * 
     * @return JsonResponse
     */
    public function inventory(): JsonResponse
    {
        $settings = SettingsService::getInventorySettings();

        return response()->json([
            'status' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Toggle inventory ledger system
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleInventoryLedger(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->enabled) {
            SettingsService::enableInventoryLedger();
            $message = 'Inventory ledger system enabled successfully';
        } else {
            SettingsService::disableInventoryLedger();
            $message = 'Inventory ledger system disabled successfully';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'use_ledger_system' => SettingsService::isInventoryLedgerEnabled(),
                'ledger_start_date' => SettingsService::getLedgerStartDate(),
            ],
        ]);
    }

    /**
     * Get all settings groups
     * 
     * @return JsonResponse
     */
    public function groups(): JsonResponse
    {
        $groups = Setting::select('group')
            ->distinct()
            ->pluck('group');

        return response()->json([
            'status' => true,
            'data' => $groups,
        ]);
    }
}


