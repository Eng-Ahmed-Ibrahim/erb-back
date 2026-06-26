<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderPayableController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date.from' => 'nullable|date',
            'date.to' => 'nullable|date',
            'order_code' => 'nullable|string',
            'receipt_number' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = OrderPayable::query()
            ->with(['order.client'])
            ->when($request->input('date.from'), fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($request->input('date.to'), fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($request->order_code, fn ($q) => $q->whereHas('order', fn ($orderQuery) => $orderQuery->where('code', 'LIKE', '%' . $request->order_code . '%')))
            ->when($request->receipt_number, fn ($q) => $q->where('receipt_number', 'LIKE', '%' . $request->receipt_number . '%'))
            ->orderBy('created_at', 'desc');

        $paginated = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => collect($paginated->items())->map(fn (OrderPayable $orderPayable) => $this->transform($orderPayable))->values(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $orderPayable = OrderPayable::with(['order.client'])->findOrFail($id);

        return responder()->success($this->transform($orderPayable))->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        $orderPayable = OrderPayable::create($validated);
        $orderPayable->load(['order.client']);

        return responder()->success($this->transform($orderPayable))->respond(Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        $orderPayable = OrderPayable::findOrFail($id);
        $orderPayable->update($validated);
        $orderPayable->load(['order.client']);

        return responder()->success($this->transform($orderPayable))->respond(Response::HTTP_OK);
    }

    public function delete(string $id)
    {
        $orderPayable = OrderPayable::findOrFail($id);
        $orderPayable->delete();

        return responder()->success(['message' => 'تم حذف مدفوعة الأوردر بنجاح'])->respond(Response::HTTP_OK);
    }

    public function orderOptions()
    {
        $orders = Order::query()
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->limit(300)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'code' => $order->code,
                'total_price' => $order->total_price,
                'client_name' => $order->client?->name,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
            ]);

        return responder()->success($orders)->respond(Response::HTTP_OK);
    }

    private function transform(OrderPayable $orderPayable): array
    {
        return [
            'id' => $orderPayable->id,
            'amount' => $orderPayable->amount,
            'note' => $orderPayable->note,
            'receipt_number' => $orderPayable->receipt_number,
            'order_id' => $orderPayable->order_id,
            'registration_date' => optional($orderPayable->created_at)->format('Y-m-d H:i'),
            'order' => [
                'id' => $orderPayable->order?->id,
                'code' => $orderPayable->order?->code,
                'total_price' => $orderPayable->order?->total_price,
                'client_name' => $orderPayable->order?->client?->name,
            ],
        ];
    }
}

