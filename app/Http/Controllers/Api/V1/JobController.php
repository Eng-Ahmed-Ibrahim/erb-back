<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::when(isset($request->job), fn ($query) => $query
            ->whereRaw('name LIKE ?', ['%'.$request->job.'%']))
            ->get();

        return responder()->success([
            'jobs' => $jobs,
            'count' => $jobs->count(),
        ])->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer',
        ]);

        $job = Job::create([
            'name' => $validated['name'],
            'points' => $validated['points'],
        ]);

        return responder()->success($job)->respond(Response::HTTP_CREATED);

    }

    public function show($uuid)
    {
        $job = Job::where('id', $uuid)->first();

        if (! $job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return responder()->success($job)->respond(Response::HTTP_OK);
    }

    public function update(Request $request, $uuid)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer',
        ]);

        $job = Job::where('id', $uuid);

        if (! $job->exists()) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Update the Job instance
        $job->update([
            'name' => $validated['name'],
            'points' => $validated['points'],
        ]);

        // update the employee and the incentives table
        // Employee::where('job_id', $job->id)->update([
        //     'points' => $job->points
        // ]);

        // Incentive::where('job_id', $job->id)->update([
        //     'points' => $job->points
        // ]);

        return responder()->success($job)->respond(Response::HTTP_OK);
    }

    public function destroy($uuid)
    {
        $job = Job::where('id', $uuid);

        if (! $job->exists()) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $job->delete();

        return responder()->success(['message' => 'Job deleted successfully'])->respond(Response::HTTP_OK);
    }
}
