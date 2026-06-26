<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdditionalServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AdditionalService::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('active_only') && $request->get('active_only')) {
            $query->where('is_active', true);
        }

        $services = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:additional_services',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_per_day' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $service = AdditionalService::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $service
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AdditionalService $additionalService)
    {
        return response()->json([
            'success' => true,
            'data' => $additionalService
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AdditionalService $additionalService)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:additional_services,code,' . $additionalService->id,
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'is_per_day' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        $additionalService->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $additionalService
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdditionalService $additionalService)
    {
        $additionalService->delete();

        return response()->json([
            'success' => true,
            'message' => 'Additional service deleted successfully'
        ]);
    }
}