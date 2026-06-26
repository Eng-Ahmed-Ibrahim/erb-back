<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\addProductRequest;
use App\Http\Requests\Order\ReportOfProductRequest;
use App\Http\Requests\Order\SearchOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderCommentRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Requests\Order\updateProductRequest;
use App\Jobs\PushOrderNotificationJob;
use App\Models\Client;
use App\Models\ClientTypeClient;
use App\Models\DeletedOrder;
use App\Models\Department;
use App\Models\ModelHasModel;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\OrderPayable;
use App\Models\OrderProduct;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Client\ClientRepository;
use App\Repositories\ClientType\ClientTypeRepository;
use App\Repositories\DiscountReason\DiscountReasonRepository;
use App\Repositories\Order\OrderRepository;
use App\Service\Order\OrderMonthlyDiscountLimitService;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use App\Service\Reports\SalesReports\SalesReportsStatistics;
use App\Transformers\Client\AbstractClientTransformer;
use App\Transformers\ClientType\AbstractClientTypeTransformer;
use App\Transformers\DiscountReason\DiscountReasonTransformer;
use App\Transformers\Order\DeletedOrderTransformer;
use App\Transformers\Order\OrderTableTransformer;
use App\Transformers\Order\OrderTransformer;
use App\Transformers\Order\OrderTransformerAfterCreate;
use App\Transformers\Order\ShowExtranalOrdersTransformer;
use App\Transformers\PaymentMethod\AbstractPaymentMethodTransformer;
use App\Transformers\PaymentMethod\PaymentMethodTransformer;
use Carbon\carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use League\Fractal\Resource\Collection;
use League\Fractal\Manager;
use App\Models\ClientType;
use DB;

class OrderController extends Controller
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PaymentMethodRepository $PaymentMethodRepository,
        private ClientTypeRepository $clientTypeRepository,
        private ClientRepository $clientRepository,
        private DiscountReasonRepository $discountReasonRepository,
        private OrderMonthlyDiscountLimitService $orderMonthlyDiscountLimitService
    ) {
        $this->orderRepository = $orderRepository;
        $this->clientTypeRepository = $clientTypeRepository;
        $this->clientRepository = $clientRepository;
        $this->PaymentMethodRepository = $PaymentMethodRepository;
        $this->discountReasonRepository = $discountReasonRepository;
    }
//        $result = $this->orderRepository->adminCreate($data);
    public function paymentMethod($client_type_id)
    {
        $clientType = $this->clientTypeRepository->find($client_type_id);
        $paymentMethods = $clientType->paymentMethods();

        return responder()->success($paymentMethods, AbstractPaymentMethodTransformer::class)->respond(Response::HTTP_OK);
    }

    public function clientsType()
    {
        $ClientType = $this->clientTypeRepository->all();

        return responder()->success($ClientType, AbstractClientTypeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function clients($client_type_id)
    {
        // $Clients = $this->clientRepository->where('client_type_id',$client_type_id,'=')->get();

        $Clients = Client::whereIn('id', ClientTypeClient::where('client_type_id', $client_type_id, '=')->pluck('client_id'));

        return responder()->success($Clients, AbstractClientTransformer::class)->respond(Response::HTTP_OK);
    }

    public function discountReasons()
    {
        $reasons = $this->discountReasonRepository->all();

        return responder()->success($reasons, DiscountReasonTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreOrderRequest $request)
    {
      try {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            if (isset($data['client_id']) && !empty($data['client_id'])) {
                $clientType = ClientType::find($data['client_type_id']);

                if (
                    $clientType &&
                    $clientType->one_order_per_day &&
                    Order::hasOrderToday($data['client_type_id'], $data['client_id'])
                ) {
                    return responder()
                        ->error('validation_error', 'هذا العميل لديه طلب واحد مسموح به في اليوم')
                        ->respond(Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            $discountLimitError = $this->orderMonthlyDiscountLimitService->validateOrderCreate($data);

            if ($discountLimitError !== null) {
                return responder()
                    ->error('validation_error', $discountLimitError['message'])
                    ->respond(Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $result = $this->orderRepository->adminCreate($data);
            if ($result['status']) {
                return responder()
                    ->success($result['orders'], OrderTransformerAfterCreate::class)
                    ->respond(Response::HTTP_CREATED);
            }

            return responder()
                ->error('message', $result['message'])
                ->respond(Response::HTTP_BAD_REQUEST);
        });
    } catch (\Throwable $e) {
        return responder()
            ->error('server_error', $e->getMessage())
            ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    }
 
    public function update(UpdateOrderRequest $request, $Order_id)
    {
        $Orders = $this->orderRepository->find($Order_id);
        $this->orderRepository->adminUpdate($Orders, $request->validated());

        return responder()->success(['message' => 'تم تعديل المشتريات بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($Order_id)
    {
        $Orders = $this->orderRepository->find($Order_id);
        $this->orderRepository->adminDelete($Orders);

        return responder()->success(['message' => 'تم حذف المشتريات بنجاح'])->respond(Response::HTTP_OK);
    }


    public function updateStatus(UpdateOrderStatusRequest $request, $Order_id)
    {
        $order = $this->orderRepository->find($Order_id);
        $table_number = $order->table_number ?? null;

        $m = $this->orderRepository->adminUpdateStatus($order, $request->validated());
        if ($m) {
            $order = $this->orderRepository->find($Order_id);

            return responder()
                ->success(OrderTableTransformer::transform($order, $table_number))
                ->respond(Response::HTTP_OK);
        }

        return responder()->error('cant_excute', 'هذا الاوردار تم تنفيذه من قبل')->respond(Response::HTTP_BAD_REQUEST);
    }

    public function updateComment(UpdateOrderCommentRequest $request, $Order_id)
    {
        $order = $this->orderRepository->find($Order_id);

        $status = $this->orderRepository->adminUpdate($order, $request->validated());
        if ($status) {
            return responder()->success($order)->respond(Response::HTTP_OK);
        }

        return responder()->error('cant_update', 'حدث خطأ أثناء اضافة الملاحظة')->respond(Response::HTTP_BAD_REQUEST);
    }

    public function updatePaymentMethod(Request $request, $Order_id)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required',
        ]);

        $paymentMethod = PaymentMethod::find($validated['payment_method_id']);

        $order = $this->orderRepository->find($Order_id);

        $status = $this->orderRepository->adminUpdate($order, ['payment_method_id' => $validated['payment_method_id'], 'payment_method' => $paymentMethod->type]);
        if ($status) {
            return responder()->success($order)->respond(Response::HTTP_OK);
        }

        return responder()->error('cant_update', 'حدث خطأ أثناء التعديل')->respond(Response::HTTP_BAD_REQUEST);
    }

    public function addProduct(addProductRequest $request, $id)
    {
        $Order = $this->orderRepository->find($id);
        $Order->update([
            'is_printed' => 0,
        ]);

        $data = $this->orderRepository->addProduct($Order, $request->validated());
        if (isset($data) && $data['status']) {
            return responder()->success($data['model'], OrderTransformer::class)->respond(Response::HTTP_CREATED);
        }

        return responder()->error('message', $data['message'])->respond(Response::HTTP_BAD_REQUEST);
    }

    public function deleteProduct(Request $request, $id)
    {
        $message = $request->input('message', ' ');
        $orderProduct = OrderProduct::findOrFail($id);
        $this->orderRepository->deleteProduct($orderProduct, $message);

        return responder()->success(['message' => 'تم حذف المنتج بنجاح'])->respond(Response::HTTP_OK);
    }

    public function updateProduct($id, updateProductRequest $request)
    {
        $order = OrderProduct::findOrFail($id);
        $this->orderRepository->updateProduct($order, $request->validated());

        return responder()->success(['message' => 'تم تعديل كمية المنتج في المشتريات بنجاح'])->respond(Response::HTTP_OK);
    }

    public function index(SearchOrderRequest $request)
    {
        $kitchenDepartments = ['01j45gtesjz0mm3qf0sz6bzvn9', '01jcb48sx8nras7ggfv5w3x4bn'];

        $data = $request->validated();
        $reportUserId = (string) ($data['user_id'] ?? Auth::user()?->id());
        $forcedPack = ModelHasModel::getForcedOrdersFilterIds($reportUserId);
        $forcedClientTypeIds = null;
        $forcedPaymentMethodIds = null;
        if ($forcedPack['apply']) {
            $forcedClientTypeIds = $forcedPack['client_type_ids'];
            $forcedPaymentMethodIds = $forcedPack['payment_method_ids'];
            unset($data['client_type_id'], $data['payment_method_id']);
        }

        $date = [];
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        // else {
        //     $date['from'] = carbon::now()->subHours(24);
        //     $date['to'] = carbon::now();
        // }

        if (isset($data['status']) && $data['status'] == 'printed') {
            $data['is_printed'] = 1;
            unset($data['status']);
        }
        if (isset($data['show_history'])) {
            $showHistory = $data['show_history'];
            unset($data['show_history']);
        }
        if (count($data) > 0) {
            if (isset($data['casher']) && $data['casher']) {
                $casher = $data['casher'];
                unset($data['casher']);
                $ordersQuery = $this->orderRepository->getInterceptedByAttributes2($data, 'created_at', 'desc');
                $ordersQuery
                ->where("status","!=","failed_print")
                ->whereHas('user', function ($query) use ($casher) {
                    $query->where('name', 'LIKE', "%$casher%");
                });
            } else {
                $ordersQuery = $this->orderRepository->getInterceptedByAttributes2($data, 'created_at', 'desc');
            }

            if ((isset($data['user_id']) && $data['user_id']) || (isset($data['department_id']) && $data['department_id'])) {
                if (isset($data['user_id']) && $data['user_id']) {
                    $user_id = $data['user_id'];
                    unset($data['user_id']);
                    $d = null;

                    if (isset($data['selected_department'])) {
                        $d = $data['selected_department'];
                        unset($data['selected_department']);
                    }

                    $filteredData = $data;
                    if (isset($filteredData['department_id'])) {
                        unset($filteredData['department_id']);
                    }

                    $ordersQuery = $this->orderRepository->getInterceptedByAttributes2($filteredData, 'orders.created_at', 'desc');

                    $userRoles = User::find($user_id)->roles()->pluck('id')->toArray();
                    if (in_array(Role::CASHIER_ROLE_ID, $userRoles)) {
                        $ordersQuery->where('user_id', $user_id);
                    } elseif (in_array(Role::EXTERNAL_ORDERS_CASHIER_ROLE_ID, $userRoles)) {
                        $ordersQuery->when(isset($data['department_id']) && $data['department_id'], fn($q) => $q->where('department_id', $data['department_id']));
                    } else {
                        if (in_array(Role::KITCHEN_ROLE_ID, $userRoles)) {
                            $ordersQuery
                                ->whereIn('department_id', Order::KITCHEN_DEPARTMENTS)
                                ->when(isset($showHistory) && $showHistory == 0, fn($query) => $query
                                    // ->whereNot('status', '=', 'completed')
                                    ->whereNot('status', '=', 'returned')
                                    // );
                                    ->whereNot('status', '=', 'closed'));
                        } elseif (in_array(Role::EXTERNAL_ORDERS_CHIEF_ROLE_ID, $userRoles) ||in_array(Role::COST_CONTROLLER_ROLE_ID, $userRoles)  ) {
                            $ordersQuery
                                ->where('department_id', Department::EXTERNAL_ORDERS_DEPARTMENT)
                                ->when(isset($showHistory) && $showHistory == 0, fn($query) => $query
                                    // ->whereNot('status', '=', 'completed')
                                    ->whereNot('status', '=', 'returned')
                                    // );
                                    ->whereNot('status', '=', 'closed'));
                        }

                        if (isset($data['department_id']) && $data['department_id']) {
                            unset($data['department_id']);
                        }
                        if ($d) {
                            $data['department_id'] = $d;
                        }
                    }
                }

                if (isset($data['department_id']) && $data['department_id']) {
                    $department_id = $data['department_id'];
                    unset($data['department_id']);
                    $ordersQuery = $ordersQuery->where('department_id', $department_id)->orderBy('created_at', 'desc');
                }
            } else {
                $ordersQuery = $this->orderRepository->getInterceptedByAttributes2($data, 'created_at', 'desc');
            }

            if (!empty($date) && $date) {
                $ordersQuery
                    ->when(isset($date['from']), function ($q) use ($date) {
                        $q->where(function ($subQuery) use ($date) {
                            $subQuery
                                ->where('created_at', '>=', $date['from'])
                                ->orWhere('closed_at', '>=', $date['from']);
                        });
                    })
                    ->when(isset($date['to']), function ($q) use ($date) {
                        $q->where(function ($subQuery) use ($date) {
                            $subQuery
                                ->where('created_at', '<=', $date['to'])
                                ->orWhere('closed_at', '<=', $date['to']);
                        });
                    });
            }

            if ($forcedClientTypeIds !== null && $forcedPaymentMethodIds !== null) {
                $ordersQuery->whereIn('client_type_id', $forcedClientTypeIds)
                    ->whereIn('payment_method_id', $forcedPaymentMethodIds);
            }

            $orders = $ordersQuery->get();

            $fractal = new Manager;
            $paginatedOrders = $this->orderRepository->paginate($orders, 40);

            $resource = new Collection($paginatedOrders, new OrderTransformer);
            $transformedOrders = $fractal->createData($resource)->toArray();

            $non_printed_orders_count = $ordersQuery->where('is_printed', 0)->get()->count();

            return response()->json([
                'success' => true,
                'data' => $transformedOrders['data'],  // Transformed invoice data
                'orders_count' => $non_printed_orders_count,
                'pagination' => [
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'per_page' => $paginatedOrders->perPage(),
                    'total' => $paginatedOrders->total(),
                ],
            ]);
        }

        $orders = $this->orderRepository->allPaginated('created_at', 'desc', [['is_printed', 'asc']]);

        return responder()->success($orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function showTables()
    {
        $userRoles = User::find(Auth::user('api')->id)->roles()->pluck('id')->toArray();

        $departmentId = in_array(Role::EXTERNAL_ORDERS_CHIEF_ROLE_ID, $userRoles) ? Department::EXTERNAL_ORDERS_DEPARTMENT : Auth::user('api')->department_id;
        $Orders = $this->orderRepository->getByAttributesWithOperators(['department_id' => $departmentId, 'status' => '!=,closed'], 'table_number', 'asc')->whereNotNull('table_number');

        return responder()->success($Orders, OrderTableTransformer::class)->respond(Response::HTTP_OK);
    }

    public function showTables2($department_id)
    {
        $Orders = $this->orderRepository->getByAttributesWithOperators(['department_id' => $department_id, 'status' => '!=,closed'], 'table_number', 'asc')->whereNotNull('table_number');

        return responder()->success($Orders, OrderTableTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show($Order_id)
    {
        $Orders = $this->orderRepository->find($Order_id);

        return responder()->success($Orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function department($department_id)
    {
        $Orders = $this->orderRepository->where('department_id', $department_id, '=')->get();

        return responder()->success($Orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function client($client_id)
    {
        $Orders = $this->orderRepository->getByAttributes(['client_id' => $client_id]);

        return responder()->success($Orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function clientType($client_type_id)
    {
        $Orders = $this->orderRepository->whereHas('client', 'client_type_id', $client_type_id)->get();

        return responder()->success($Orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function postpaid($type)
    {
        $Orders = $this->orderRepository->whereHas('paymentMethod', 'type', $type)->get();

        return responder()->success($Orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function payment_method()
    {
        $Orders = $this->PaymentMethodRepository->get();

        return responder()->success($Orders, PaymentMethodTransformer::class)->respond(Response::HTTP_OK);
    }

    public function status($status)
    {
        $Orders = $this->orderRepository->where('status', $status, '=')->get();

        return responder()->success($Orders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function execute($id)
    {
        $Orders = $this->orderRepository->execute($id);

        return responder()->success(['message' => 'تم تنفيذ الاوردر بنجاح'])->respond(Response::HTTP_CREATED);
    }

    public function checkTableNum($table_num)
    {
        $Order = $this
            ->orderRepository
            ->getByAttributesWithOperators(['status' => '!=,closed'])
            ->where('department_id', Auth::user('api')->department_id)
            ->where('table_number', $table_num)
            ->first();

        if ($Order) {
            return responder()->success(['message' => false])->respond(Response::HTTP_CREATED);
        }

        return responder()->success(['message' => true])->respond(Response::HTTP_CREATED);
    }

    public function reportOfProduct(ReportOfProductRequest $request)
    {
        $data = $request->validated();
        $date = null;
        if (isset($data['from']) && $data['from']) {
            $date = ['from' => $data['from'], 'to' => $data['to']];
        }

        $report = new SalesReportsStatistics;
        $Orders = $report->getSalesReports($data['department_id'], $data['product_id'], $date);

        return responder()->success($Orders)->respond(Response::HTTP_OK);
    }

    public function isPrinted($order_id)
    {
        $order = $this->orderRepository->find($order_id);
        $order->update([
            'is_printed' => true,
        ]);

        if ($order) {
            $order->products()->update([
                'recieved_quantity' => \DB::raw('quantity'),
            ]);
        }

        return responder()->success(['message' => 'تمت الإستلام بنجاح'])->respond(Response::HTTP_OK);
    }

    public function ordersReport(Request $request)
    {
        $data = $request->input('data', '');

        $reports = $this->orderRepository->ordersReport($data);

        return responder()->success($reports)->respond(Response::HTTP_OK);
    }

    public function reviewOrderPrice(Request $request)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'client_id' => 'nullable|exists:clients,id',
            'client_type_id' => 'required|exists:client_types,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        $orderPrice = $this->orderRepository->reviewOrderPrice($data);

        return responder()->success($orderPrice)->respond(Response::HTTP_OK);
    }

    public function monthlyDiscountStatus(Request $request)
    {
        $data = $request->validate([
            'products' => 'nullable|array',
            'client_id' => 'nullable|string',
            'client_type_id' => 'required|exists:client_types,id',
            'department_id' => 'required|exists:departments,id',
            'name' => 'nullable|string|max:255',
        ]);

        $status = $this->orderMonthlyDiscountLimitService->getMonthlyDiscountStatus($data);

        return responder()->success($status)->respond(Response::HTTP_OK);
    }

    public function paymentReport(Request $request)
    {
        $data = $request->input('data', []);

        $reports = $this->orderRepository->paymentReport($data);

        return responder()->success($reports)->respond(Response::HTTP_OK);
    }

    public function getDeoartmentProductsReport(Request $request)
    {
        $data = $request->input('data', []);

        $reports = $this->orderRepository->getDeoartmentProductsReport($data);
        $result = [];
        $result['orders'] = $reports->get()->toArray();
        $result['total'] = $reports->get()->sum('price');

        return responder()->success($result)->respond(Response::HTTP_OK);
    }

    public function storePayable(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'note' => 'nullable',
            'receipt_number' => 'nullable',
        ]);

        $payable = OrderPayable::create([
            'note' => $request->note,
            'amount' => $request->amount,
            'order_id' => $id,
            'receipt_number' => $request->receipt_number,
        ]);

        return responder()->success($payable)->respond(Response::HTTP_OK);
    }

    public function externalOrdersReport(Request $request)
    {
        $date = $request->input('data', []);
        $orders = $this
            ->orderRepository
            ->getInterceptedByAttributes2(['client_type_id' => '01hzf60qrasrm5x2ytvyrsne1j'], 'created_at', 'desc')
            ->when(isset($date) && $date['from'], fn($q) => $q
                ->where('created_at', '>=', $date['from']))
            ->when(isset($date) && $date['to'], fn($q) => $q
                ->where('created_at', '<=', Carbon::parse($date['to'])->endOfDay()))
            ->where('created_at', '>=', '2024-11-20');

        return responder()->success($orders, ShowExtranalOrdersTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getDeletedOrders(SearchOrderRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        $orders = DeletedOrder::query()
            ->when(isset($data) && isset($data['selected_department']), fn($query) => $query
                ->where('department_id', $data['selected_department']))
            ->when(isset($data) && isset($data['code']), fn($query) => $query
                ->where('code', $data['code']))
            ->when(isset($data) && isset($data['status']), fn($query) => $query
                ->where('status', $data['status']))
            ->when(isset($date) && isset($date['from']), fn($query) => $query
                ->where('created_at', '>=', $date['from']))
            ->when(isset($date) && isset($date['to']), fn($query) => $query
                ->where('created_at', '<=', Carbon::parse($date['to'])->endOfDay()))
            ->get();

        return responder()->success($orders, DeletedOrderTransformer::class)->respond(Response::HTTP_OK);
    }

    public function updateDeletedOrderStatus(UpdateOrderStatusRequest $request, $id)
    {
        $deletedOrder = DeletedOrder::findorFail($id);
        $status = $deletedOrder->update(['status' => $request->status, 'reviewed_by' => auth()->user()->id]);
        if ($status) {
            return responder()
                ->success($deletedOrder, DeletedOrderTransformer::class)
                ->respond(Response::HTTP_OK);
        }
    }
}
