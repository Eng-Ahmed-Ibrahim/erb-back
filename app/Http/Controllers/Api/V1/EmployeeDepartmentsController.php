<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDepartments;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeDepartmentsController extends Controller
{
    public function index(Request $request)
    {
        $departments = EmployeeDepartments::when(isset($request->name), fn ($query) => $query
            ->whereRaw('name LIKE ?', ['%'.$request->name.'%']))
            ->get();

        return responder()->success([
            'departments' => $departments,
            'count' => $departments->count(),
        ])->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points_percentage' => 'required|numeric',
        ]);

        $department = EmployeeDepartments::create([
            'name' => $validated['name'],
            'points_percentage' => $validated['points_percentage'],
        ]);

        return responder()->success($department)->respond(Response::HTTP_CREATED);
    }

    public function update(Request $request, $uuid)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points_percentage' => 'required|numeric',
        ]);

        $department = EmployeeDepartments::where('id', $uuid);

        if (! $department->exists()) {
            return response()->json(['message' => 'Employee department not found'], 404);
        }

        $department->update([
            'name' => $validated['name'],
            'points_percentage' => $validated['points_percentage'],
        ]);

        return responder()->success($department)->respond(Response::HTTP_OK);
    }

    public function destroy($uuid)
    {
        $department = EmployeeDepartments::where('id', $uuid);

        if (! $department->exists()) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $department->delete();

        return responder()->success(['message' => 'department deleted successfully'])->respond(Response::HTTP_OK);
    }
}
