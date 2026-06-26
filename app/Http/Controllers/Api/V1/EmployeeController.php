<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDepartments;
use App\Models\EmployeeType;
use App\Models\Incentive;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['job', 'department'])
            ->when(isset($request->department), fn($query) => $query
                ->whereHas('department', fn($q) => $q->where('id', $request->department)))
            ->when(isset($request->name), fn($query) => $query
                ->whereRaw('name LIKE ?', ['%' . $request->name . '%']))
            ->when(isset($request->job), fn($query) => $query
                ->whereHas('job', fn($q) => $q->whereRaw('name LIKE ?', ['%' . $request->job . '%'])))
            ->when(isset($request->employee_type), fn($query) => $query
                ->where('employee_type_id', $request->employee_type))
            ->when(isset($request->national_id), fn($query) => $query
                ->whereRaw('national_id LIKE ?', ['%' . $request->national_id . '%']))
            ->get();

        return responder()->success(['employees' => $employees,
            'total' => $employees->sum('total_employees'),
            'count' => $employees->count()])->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'national_id' => 'required|string|max:255|unique:employees',
            'name' => 'required|string|max:255',
            'job_id' => 'required|exists:employees_jobs,id',
            'department_id' => 'required',
            'points' => 'nullable|double',
            'employee_type_id' => 'required',
        ]);

        $job = Job::find($validated['job_id']);

        $employee = Employee::create([
            'national_id' => $validated['national_id'],
            'name' => $validated['name'],
            'job_id' => $validated['job_id'],
            'department_id' => $validated['department_id'],
            'points' => $job->points,
            'employee_type_id' => $validated['employee_type_id'],
        ]);

        $this->createIncentiveForEmployee($employee);

        return responder()->success($employee)->respond(Response::HTTP_CREATED);
    }

    protected function createIncentiveForEmployee(Employee $employee)
    {
        Incentive::create([
            'employee_id' => $employee->id,
            'job_id' => $employee->job_id,
            'discount' => 0,
            'reward' => 0,
            'total_incentives' => 0,
            'points' => $employee->points,
        ]);
    }

    public function show($uuid)
    {
        $employee = Employee::with(['job', 'department'])->where('id', $uuid)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employe00e not found'], 404);
        }

        return responder()->success($employee)->respond(Response::HTTP_OK);
    }

    public function update(Request $request, $uuid)
    {
        $validated = $request->validate([
            'national_id' => 'required|string|max:255|unique:employees,national_id,' . $uuid . ',id',
            'name' => 'required|string|max:255',
            'job_id' => 'required|exists:employees_jobs,id',
            'department_id' => 'required',
            'points' => 'nullable|double',
            'employee_type_id' => 'required',
        ]);

        $job = Job::where('id', $validated['job_id'])->first();

        Employee::where('id', $uuid)->update([
            'national_id' => $validated['national_id'],
            'name' => $validated['name'],
            'job_id' => $validated['job_id'],
            'department_id' => $validated['department_id'],
            'points' => $job->points,
            'employee_type_id' => $validated['employee_type_id'],
            'is_active' => 1
        ]);

        if (!Incentive::where('employee_id', $uuid)->exists()) {
            $this->createIncentiveForEmployee(Employee::find($uuid));
        }

        // Incentive::where('employee_id', $uuid)->update([
        //     'points' => $job->points,
        // ]);

        Incentive::query()
            ->join('employees', 'incentives.employee_id', '=', 'employees.id')
            ->join('employees_departments', 'employees.department_id', '=', 'employees_departments.id')
            ->where('incentives.employee_id', $uuid)
            ->update([
                'incentives.job_id' => $validated['job_id'],
                'total_incentives' => DB::raw('
                            (COALESCE(incentives.points, 0)  + COALESCE(incentives.reward, 0) - COALESCE(incentives.discount, 0)) 
                            * COALESCE(incentives.point_value, 0) 
                            * COALESCE(employees_departments.points_percentage / 100 , 1) - incentives.advance - incentives.other_deductions - incentives.sim_card_deduction'),
            ]);

        return responder()->success(['message' => 'تم التعديل بنجاح'])->respond(Response::HTTP_OK);
    }

    public function destroy($uuid)
    {
        $employee = Employee::find($uuid);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        Incentive::where('employee_id', $uuid)
            ->delete();

        $employee->is_active = 0;
        $employee->save();

        return responder()->success(['message' => 'Employee deleted successfully'])->respond(Response::HTTP_OK);
    }

    public function departments()
    {

        return EmployeeDepartments::all();
    }

    public function getEmployyeTypes()
    {
        $types = EmployeeType::get();

        return responder()->success($types)->respond(Response::HTTP_OK);
    }
}
