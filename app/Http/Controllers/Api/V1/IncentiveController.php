<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\IncentivesArchive;
use App\Models\IncentiveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncentiveController extends Controller
{
    public function index(Request $request)
    {
        $canEdit = false;
        $incentives = [];
        $type = $request->type ?? 1;
        $latestIncentivesArchiveMonth = IncentivesArchive::where('type', $type)->max('month');

        if ($request->month) {
            if ($request->month > $latestIncentivesArchiveMonth) {
                $canEdit = true;
                Log::info('in incentives month ');
                $incentivesQuery = Incentive::with(['employee', 'job', 'employee.department'])
                    ->whereHas('employee', fn($q) => $q->where('is_active', 1))
                    ->orderBy('incentives.updated_at', 'desc');
            } else {
                Log::info('in IncentivesArchive month ');

                $incentivesQuery = IncentivesArchive::with(['employee', 'job', 'employee.department'])
                    ->where('type', $type)
                    ->where('month', Carbon::parse($request->month)->format('Y-m-d'));
            }
        } else {
            Log::info('in incentives  ');

            $incentivesQuery = Incentive::with(['employee', 'job', 'employee.department'])
                ->whereHas('employee', fn($q) => $q->where('is_active', 1))
                ->orderBy('incentives.updated_at', 'desc');
        }

        $incentives = $incentivesQuery
            ->when(isset($request->employee_type), fn($query) => $query
                ->whereHas('employee', fn($q) => $q->where('employee_type_id', $request->employee_type)))
            ->when(isset($request->department), fn($query) => $query
                ->whereHas('employee.department', fn($q) => $q->where('id', $request->department)))
            ->when(isset($request->name), fn($query) => $query
                ->whereHas('employee', fn($q) => $q->whereRaw('name LIKE ?', ['%' . $request->name . '%'])))
            ->when(isset($request->job), fn($query) => $query
                ->whereHas('job', fn($q) => $q->whereRaw('name LIKE ?', ['%' . $request->job . '%'])))
            ->when(isset($request->national_id), fn($query) => $query
                ->whereHas('employee', fn($q) => $q->whereRaw('national_id LIKE ?', ['%' . $request->national_id . '%'])))
            ->when($request->has('has_excellence_bonus') && $request->has_excellence_bonus !== 'all', fn($query) => $query
                ->where(function ($q) use ($request) {
                    if (in_array($request->has_excellence_bonus, ['1', 1, true, 'true'], true)) {
                        $q->whereRaw('COALESCE(excellence_bonus, 0) > 0');
                    } elseif (in_array($request->has_excellence_bonus, ['0', 0, false, 'false'], true)) {
                        $q->whereRaw('COALESCE(excellence_bonus, 0) = 0');
                    }
                }))
            ->get();

        $isExcellenceOnly = in_array($request->has_excellence_bonus, ['1', 1, true, 'true'], true);
        $total = $isExcellenceOnly
            ? $incentives->sum(function ($incentive) {
                $bonusPoints = (float) ($incentive->excellence_bonus ?? 0);
                $pointValue = (float) ($incentive->point_value ?? 0);
                $departmentPercentage = (float) ($incentive->employee->department->points_percentage ?? 100) / 100;

                return $bonusPoints * $pointValue * $departmentPercentage;
            })
            : $incentives->sum('total_incentives');

        return responder()->success(
            [
                'incentives' => $incentives,
                'total' => number_format($total, 2),
                'count' => $incentives->count(),
                'can_edit' => $canEdit,
            ]
        )->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'job_id' => 'required|exists:jobs,id',
            'discount' => 'required|numeric',
            'reward' => 'required|numeric',
            'total_incentives' => 'required|numeric',
            'points' => 'required|numeric',
        ]);

        // Create a new incentive
        $incentive = Incentive::create($validated);

        return responder()->success($incentive)->respond(Response::HTTP_CREATED);
    }

    // Show a single incentive
    public function show($uuid)
    {
        $incentive = Incentive::with(['employee', 'job'])->find($uuid);

        if (!$incentive) {
            return response()->json(['message' => 'Incentive not found'], 404);
        }

        return responder()->success($incentive)->respond(Response::HTTP_OK);
    }

    public function update(Request $request, $uuid)
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'job_id' => 'nullable|exists:jobs,id',
            'discount' => 'nullable|numeric',
            'reward' => 'nullable|numeric',
            'total_incentives' => 'nullable|numeric',
            'points' => 'nullable|numeric',
            'excellence_bonus' => 'nullable|numeric',
            'advance' => 'nullable|numeric',
            'other_deductions' => 'nullable|numeric',
            'sim_card_deduction' => 'nullable|numeric',
        ]);
        $incentive = Incentive::find($uuid);

        if (!$incentive) {
            return response()->json(['message' => 'Incentive not found'], 404);
        }

        $incentive->update($validated);
        $this->calcEmployeeTotalIncetive($uuid);

        Employee::where('id', $incentive->employee_id)->update([
            'points' => $incentive->points,
        ]);

        return responder()->success($incentive)->respond(Response::HTTP_OK);
    }

    public function destroy($uuid)
    {
        $incentive = Incentive::find($uuid);

        if (!$incentive) {
            return response()->json(['message' => 'Incentive not found'], 404);
        }

        $incentive->delete();

        return responder()->success(['message' => 'Incentive deleted successfully'])->respond(Response::HTTP_OK);
    }

    public function updateAll(Request $request)
    {
        $validated = $request->validate([
            'point_value' => 'required|numeric',
        ]);

        Incentive::query()->update([
            'point_value' => $validated['point_value'],
        ]);

        Incentive::query()
            ->join('employees', 'incentives.employee_id', '=', 'employees.id')
            ->join('employees_departments', 'employees.department_id', '=', 'employees_departments.id')
            ->update([
                'total_incentives' => DB::raw('
            (COALESCE(incentives.points, 0)  + COALESCE(incentives.reward, 0) - COALESCE(incentives.discount, 0)) 
            * COALESCE(incentives.point_value, 0) 
            * COALESCE(employees_departments.points_percentage / 100 , 1) - incentives.advance - incentives.other_deductions - incentives.sim_card_deduction '),
            ]);

        return responder()->success(['message', 'Incentive data updated succesfully'])->respond(Response::HTTP_OK);
    }

    public function LockCurrentMonthIncentives(Request $request)
    {
        $request->validate([
            'month' => 'required',
            'type' => 'required',
        ]);

        Incentive::chunk(100, function ($incentives) use ($request) {
            $dataToInsert = [];
            foreach ($incentives as $incentive) {
                $dataToInsert[] = [
                    'employee_id' => $incentive->employee_id,
                    'job_id' => $incentive->job_id,
                    'month' => Carbon::parse($request->month)->format('Y-m-d'),
                    'discount' => $incentive->discount,
                    'reward' => $incentive->reward,
                    'total_incentives' => $incentive->total_incentives,
                    'point_value' => $incentive->point_value,
                    'points' => $incentive->points,
                    'excellence_bonus' => $incentive->excellence_bonus ?? 0,
                    'advance' => $incentive->advance,
                    'sim_card_deduction' => $incentive->sim_card_deduction,
                    'other_deductions' => $incentive->other_deductions,
                    'type' => $request->type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            IncentivesArchive::insert($dataToInsert);
        });

        // Incentive::query()->update([
        //     'reward' => 0,
        //     'discount' => 0,
        //     'total_incentives' => DB::raw('(COALESCE(points,0) + COALESCE(reward,0) - COALESCE(discount,0)) * COALESCE(point_value,0) ')
        // ]);

        Incentive::query()
            ->join('employees', 'incentives.employee_id', '=', 'employees.id')
            ->join('employees_departments', 'employees.department_id', '=', 'employees_departments.id')
            ->update([
                'reward' => 0,
                'discount' => 0,
                'advance' => 0,
                'excellence_bonus' => 0, 
                'sim_card_deduction' => 0,
                'other_deductions' => 0,
                'total_incentives' => DB::raw('
        (COALESCE(incentives.points, 0)  + COALESCE(incentives.reward, 0) - COALESCE(incentives.discount, 0)) 
        * COALESCE(incentives.point_value, 0) 
        * COALESCE(employees_departments.points_percentage / 100 , 1)  - incentives.advance - incentives.other_deductions - incentives.sim_card_deduction'),
            ]);

        return responder()->success(['message', 'Incentive saved succesfully'])->respond(Response::HTTP_OK);
    }

    private function calcEmployeeTotalIncetive($uuid = '')
    {
        Incentive::when(isset($uuid), fn($query) => $query
                ->where('incentives.id', $uuid))
            ->join('employees', 'incentives.employee_id', '=', 'employees.id')
            ->join('employees_departments', 'employees.department_id', '=', 'employees_departments.id')
            ->update([
                'total_incentives' => DB::raw('(COALESCE(incentives.points, 0) + COALESCE(incentives.reward, 0) -
                    COALESCE(incentives.discount, 0)) * COALESCE(incentives.point_value, 0)  * COALESCE(employees_departments.points_percentage / 100 , 1) -
                    incentives.advance - incentives.other_deductions - incentives.sim_card_deduction '),
            ]);
    }

    public function types()
    {
        return responder()->success(IncentiveType::all())->respond(Response::HTTP_OK);
    }
}
