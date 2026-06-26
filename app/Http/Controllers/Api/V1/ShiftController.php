<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Models\Shift;
use App\Models\User;
use App\Transformers\ShiftTransformer;
use Carbon\carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', []);

        if (!isset($date['from'])) {
            $date['from'] = carbon::now()->subHours(24);
        }

        $shifts = Shift::with(['user:id,name', 'department:id,name'])
            ->when(isset($date) && !empty($date['from']), fn($query) => $query
                ->where('start', '>=', $date['from']))
            ->when(isset($date) && !empty($date['to']), fn($query) => $query
                ->where('start', '<=', $date['to']))
            ->get();

        return responder()->success($shifts, ShiftTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreShiftRequest $request)
    {
        $data = $request->validated();

        $shift = Shift::create(
            [
                'department_id' => $data['department_id'],
                'user_id' => $data['user_id'],
                'start' => $data['start'],
                'end' => $data['end'],
            ]
        );

        return responder()->success(['message' => 'تم الحفظ بنجاح'])->respond(Response::HTTP_CREATED);
    }

    public function delete($shift_id)
    {
        $shift = Shift::find($shift_id);
        $shift->delete();

        return responder()->success(['message' => 'تم الحذف بنجاح'])->respond(Response::HTTP_OK);
    }

    public function getAllCashiers(Request $request)
    {
        $cashiers = User::whereHas('roles', function ($query) {
            $query->where('name', 'cashier')
            ->orWhere('name', 'كاشير فتح الاوردرات الخارجية')  
            ->orWhere('name', 'activities-cashier')
            ->orWhere('name', ' قسم الأغذية و المشروبات و الاوردرات الخارجية');
           
        })->get();

        return responder()->success($cashiers, \App\Transformers\User\UserTransformer::class)->respond(Response::HTTP_OK);
    }
}
