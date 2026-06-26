<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\InventoryAdjustmentInvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DepartmentOrdersRequest;
use App\Http\Requests\Department\SearchDepartmentRequest;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRecipePriceRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Requests\UpdateDepartmentActualQuantitiesRequest;
use App\Models\DepartmentStore;
use App\Models\ModelHasModel;
use App\Models\Role;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\User\UserRepository;
use App\Service\Factory\Invoices\InventoryAdjustmentInvoice;
use App\Transformers\Department\AbstractDepartmentTransformer;
use App\Transformers\Department\DepartmentTransformer;
use App\Transformers\Department\ShowDepartmentsTransformer;
use App\Transformers\Invoices\InvoiceTransformer;
use App\Transformers\Order\OrderTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DepartmentSection;
class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private UserRepository $userRepository
    ) {
        $this->departmentRepository = $departmentRepository;
        $this->userRepository = $userRepository;
    }

    public function index(SearchDepartmentRequest $request)
    {
        $data = $request->validated();
        $departments = [];
        if (auth()->user()->roles()->first()->id == Role::SUPPLY_ROLE_ID) {
            $departments = ['01hy3km07mf7fafqn2j6388d1t', '01jg92yrjnqptj4tm575h8j3ky'];
        }

        // $departments = array_merge($departments,
        //     ModelHasModel::query()
        //         ->where('source_model_id', auth()->user()->id)
        //         ->where('operation', ModelHasModel::Review_Operation)
        //         ->pluck('target_model_id')
        //         ->toArray());

        $addationalFilters = [];
        if (isset($data['date'])) {
            $date = $data['date'];
            $addationalFilters['from'] = $date['from'];
            $addationalFilters['to'] = $date['to'];
            unset($data['date']);
        }
        if (isset($data['warehouse_section_id'])) {
            $addationalFilters['warehouse_section_id'] = $data['warehouse_section_id'];
            unset($data['warehouse_section_id']);
        }  
        if (isset($data['include_invoices'])) {
            $addationalFilters['include_invoices'] = $data['include_invoices'];
            unset($data['include_invoices']);
        }

        if (count($data) > 0) {
            $departments = $this
                ->departmentRepository
                ->getInterceptedByAttributes2($data)
                ->when(!empty($departments), fn($q) => $q->whereIN('id', $departments))
                ->orderBy('created_at', 'desc')
                ->get();

            return responder()->success(ShowDepartmentsTransformer::transform($departments, $addationalFilters))->respond(Response::HTTP_OK);
        }

        $departments = $this
            ->departmentRepository
            ->orderBy('created_at', 'desc')
            ->when(isset(auth()->user()->roles()->first()->id) && auth()->user()->roles()->first()->id == '9c10deda-c41a-4c2c-9e5e-eb48322e038c', fn($q) => $q
                ->whereNotIn('id', ['01je6w63bzxf18trcqcenmfwfq']))
            ->when(!empty($departments), fn($q) => $q->whereIN('id', $departments))
            ->get();

        return responder()->success(ShowDepartmentsTransformer::transform($departments, $addationalFilters))->respond(Response::HTTP_OK);
    }

    public function show($department_id)
    {
        $department = $this->departmentRepository->find($department_id);

        return responder()->success($department, DepartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = $this->departmentRepository->adminCreate($request->validated());

        return responder()->success($department, AbstractDepartmentTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateDepartmentRequest $request, $department_id)
    {
        $department = $this->departmentRepository->find($department_id);
        $this->departmentRepository->adminUpdate($department, $request->validated());

        return responder()->success(['message' => 'تم تعديل القسم بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($department_id)
    {
        $department = $this->departmentRepository->find($department_id);
        $this->departmentRepository->adminDelete($department);

        return responder()->success(['message' => 'تم حذف القسم بنجاح'])->respond(Response::HTTP_OK);
    }

    public function getAllDepartment()
    {
        $departments =
            ModelHasModel::query()
                ->where('source_model_id', auth()->user()->id)
                ->where('operation', ModelHasModel::Review_Operation)
                ->pluck('target_model_id')
                ->toArray();

        if (!empty($departments)) {
            $departments = $this->departmentRepository->whereIn('id', $departments);

            return responder()->success($departments, AbstractDepartmentTransformer::class)->respond(Response::HTTP_OK);
        }

        // $departments = $this->departmentRepository->when(!empty($departments), fn($query) => $query->whereIn('id', $departments)) ;

        $departments = $this->departmentRepository->all();

        return responder()->success($departments, AbstractDepartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function orders(DepartmentOrdersRequest $request, $department_id)
    {
        if (auth()->user()->roles()->first()->id == Role::CASHIER_ROLE_ID) {
            $now = Carbon::now();
            $currentHour = $now->hour;

            if (!(($currentHour >= 7 && $currentHour < 8) ||
                    ($currentHour >= 15 && $currentHour <= 17) ||
                    ($currentHour >= 23) ||
                    ($currentHour <= 2))) {
                return responder()->error('message', 'تقارير المبيعات متاحة فقط في أخر الوردية ')->respond(503);
            }
        }
        $data = $request->validated();
        $reportUserId = (string) ($data['user_id'] ?? auth()->user()->id);
        $forcedPack = ModelHasModel::getForcedOrdersFilterIds($reportUserId);
        $applyForcedOrdersFilters = $forcedPack['apply'];
        $forcedClientTypeIds = $forcedPack['client_type_ids'];
        $forcedPaymentMethodIds = $forcedPack['payment_method_ids'];

        if ($applyForcedOrdersFilters) {
            Log::info('Department orders forced filters applied', [
                'user_id' => $reportUserId,
                'forced_client_type_ids' => $forcedClientTypeIds,
                'forced_payment_method_ids' => $forcedPaymentMethodIds,
                'department_id' => $department_id,
            ]);
            unset($data['client_type_id'], $data['payment_method_id']);
        }

        if (!isset($data['from'])) {
            $data['from'] = now()->format('Y-m-d');
        }

        if (!isset($data['to'])) {
            $data['to'] = now()->format('Y-m-d');
        }

        $department = $this->departmentRepository->find($department_id);
        $userDepartment = auth()->user()->department;
        if ($userDepartment->type != 'master') {
            $checkDate = $this->checkDate($data['from']);
            $to = Carbon::now();
            $data['user_id'] = auth()->user()->id;
            if (!$checkDate) {
                return responder()->error('Date_validation', 'لا يمكنك جلب تقرير لمده تزيد عن يوم واحد للخلف');
            }
        } else {
            $to = $data['to'] ? Carbon::parse($data['to']) : $to = Carbon::today();
        }

        $orders = $department->orders()
        ->where("status","!=","failed_print")
        
        ->latest();

        if (isset($data['user_id']) && $data['user_id']) {
            $orders = $orders->where('user_id', $data['user_id']);
        }

        if (isset($data['waiter_id']) && $data['waiter_id']) {
            $orders = $orders->where('waiter_id', $data['waiter_id']);
        }
        if (isset($data['status']) && $data['status']) {
            $orders = $orders->where('status', $data['status']);
        }
        if ($applyForcedOrdersFilters) {
            $orders = $orders
                ->whereIn('client_type_id', $forcedClientTypeIds)
                ->whereIn('payment_method_id', $forcedPaymentMethodIds);
        } else {
            if (isset($data['payment_method_id']) && $data['payment_method_id']) {
                $orders = $orders->where('payment_method_id', $data['payment_method_id']);
            }
            if (isset($data['client_type_id']) && $data['client_type_id']) {
                $orders = $orders->where('client_type_id', $data['client_type_id']);
            }
        }
        if (isset($data['client_id']) && $data['client_id']) {
            $orders = $orders->where('client_id', $data['client_id']);
        }
        $orders = $orders
            // ->where('created_at', '>=', $data['from'])
            // ->where('created_at', '<=', $to)
            // ->whereBetween('created_at', [$data['from'], $to])
            // ->orwhereBetween('closed_at', [$data['from'], $to])
            // ->where("statud","!=","pending")
            ->where(fn($q) => $q
                ->whereBetween('created_at', [$data['from'], $to])
                ->orwhereBetween('closed_at', [$data['from'], $to]))
            ->get();

        $total_cost_price = 0;
        $total_pay = 0;
        foreach ($orders as $order) {
            $total_cost_price += $order->products()->get()->sum('cost_price');
            $total_pay += $order->products()->get()->sum('price');
        }

        $total_profit = $total_pay - $total_cost_price;
        $total_taxes = $orders->sum('tax');
        $total_discounts = $orders->sum('discount');

        $totals = [
            'total_profit' => $total_profit,
            'total_visa' => $orders->where('payment_method', 'visa')->sum('total_price'),
            'total_cash' => $orders->where('payment_method', 'cash')->sum('total_price'),
            'total_post_paid' => $orders->where('payment_method', 'postpaid')->sum('total_price'),
            'total_hospitality' => $orders->where('payment_method', 'hospitality')->sum('total_price'),
            'total_taxes' => $total_taxes,
            'total_discounts' => $total_discounts,
        ];

        $formatedOrders = [];
        foreach ($orders as $order) {
            $formatedOrders[] = (new OrderTransformer)->transform($order);
        }

        $formatedOrders = array_values($formatedOrders);
        $formatedOrders['totals'] = $totals;

        return responder()->success($formatedOrders)->respond(Response::HTTP_OK);
    }

    private function checkDate($from)
    {
        if (Carbon::parse($from)->isToday() || Carbon::parse($from)->isYesterday()) {
            return true;
        }

        return false;
    }

    public function getDepartmentReciepes(Request $request)
    {
        $data = $request->input('data', []);
        $department = $this->departmentRepository->find($data['department_id']);

        return responder()->success(DepartmentTransformer::transform($department, $data))->respond(Response::HTTP_OK);
    }

    public function updateRecipesPrice(UpdateDepartmentRecipePriceRequest $request)
    {
        $data = $request->validated();

        $unit_price = $request['unit_price'];

        $department = DepartmentStore::where('department_id', $request['department_id'])
            ->where('recipe_id', $request['recipe_id']);

        $status = $department->update([
            'price' => $department->pluck('quantity')[0] * $unit_price,
        ]);

        if ($status) {
            return responder()->success(['message' => 'تم تعديل سعر المنتج بنجاح  '])->respond(Response::HTTP_OK);
        }

        return responder()->error(['message' => 'حدث خطأ أثناء  نعديل سعر المنتج '])->respond(Response::HTTP_BAD_REQUEST);
    }

    public function eliminateOverQuantity(Request $request)
    {
        $request->validate(['department_store_ids' => 'required|array']);

        DepartmentStore::whereIn('id', $request->department_store_ids)
            ->update([
                'over_quantity' => 0,
                // 'under_quantity' => 0,
            ]);

        return responder()->success(['message' => 'تم التعديل بنجاح'])->respond(Response::HTTP_OK);
    }

    public function updateDepartmentActualQuantities(UpdateDepartmentActualQuantitiesRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $recipes = collect($data['items'])->map(function ($recipe) {
                $departmentStoreRecipe = DepartmentStore::findOrFail($recipe['id']);
                if (is_numeric($recipe['actual_quantity']) && ((float) $recipe['actual_quantity'] != (float) $departmentStoreRecipe->quantity)) {
                    return [
                        'recipe_id' => $departmentStoreRecipe->recipe_id,
                        'quantity' => (float) $recipe['actual_quantity'] - (float) $departmentStoreRecipe->quantity,
                    ];
                }
            })->filter();

            if (!empty($recipes)) {
                $invoiceData = new InventoryAdjustmentInvoiceDTO([
                    'recipes' => $recipes?->toArray(),
                    'from' => $data['department_id'],
                ]);

                $invoice = (new InventoryAdjustmentInvoice)->createInvoice($invoiceData->toArray());
                if (!$invoice['status']) {
                    DB::rollBack();
                    return responder()->error('error', $invoice['message'])->respond(Response::HTTP_BAD_REQUEST);
                }

                $totalMissingQuantity = $invoice->recipes()->sum('quantity');

                DepartmentInventoryReviewController::createFromInventoryDiscrepancy([
                    'department_id' => $data['department_id'],
                    'invoice_id' => $invoice?->id,
                    'total_missing_quantity' => $totalMissingQuantity,
                    'estimated_loss_amount' => $data['estimated_loss_amount'],
                    'discrepancy_note' => $data['discrepancy_note'] ?? 'Auto-generated from inventory discrepancy',
                    'cashier_id' => $data['cashier_id'],
                    'waiter_id' => $data['waiter_id'],
                ]);

                DB::commit();
                return responder()->success($invoice, InvoiceTransformer::class)->respond(Response::HTTP_CREATED);
            }

            DB::commit();

            return responder()->success(['message' => 'تم تعديل الكميات الفعلية بنجاح'])->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            // DB::rollBack();
            Log::info('Error :', ['updates' => $e]);

            return responder()
                ->error('server_error', 'حدث خطأ أثناء حفظ الجرد')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getDepartmentSections()
    {
        $sections = DepartmentSection::all();

        return responder()->success($sections)->respond(Response::HTTP_OK);
    }
}
