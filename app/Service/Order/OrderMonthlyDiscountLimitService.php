<?php

namespace App\Service\Order;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Order\OrderRepository;
use App\Transformers\Product\CalculateProductCostPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderMonthlyDiscountLimitService
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    public function resolveClientIdForLimitCheck(array $data): ?string
    {
        $raw = $data['client_id'] ?? null;
        if ($raw !== null && $raw !== '') {
            return (string) $raw;
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $client = Client::where('name', $name)->first();

        return $client ? (string) $client->id : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payloadProducts
     * @return array<int, array{productId: string, price: float|int, quantity: int|float}>
     */
    public function buildReviewProductsFromStorePayload(array $payloadProducts): array
    {
        $mapped = [];
        foreach ($payloadProducts as $p) {
            $pid = $p['product_id'] ?? $p['productId'] ?? null;
            if (! $pid) {
                continue;
            }
            $product = Product::find($pid);
            if (! $product) {
                continue;
            }
            $mapped[] = [
                'productId' => (string) $product->id,
                'price' => $product->price,
                'quantity' => $p['quantity'] ?? 1,
            ];
        }

        return $mapped;
    }

    /**
     * Sum monetary discounts already booked for this client in the calendar month (app timezone).
     */
    public function monthlyDiscountUsed(string $clientId, ?Carbon $at = null): float
    {
        [$start, $end] = $this->monthRangeInAppTimezone($at ?? Carbon::now(config('app.timezone')));

        $sum = Order::query()
            ->where('client_id', $clientId)
            ->where('status', '!=', 'returned')
            ->whereBetween(DB::raw('COALESCE(order_date, created_at)'), [$start, $end])
            ->sum(DB::raw('COALESCE(discount, 0)'));

        return round((float) ($sum ?? 0), 2);
    }

    /**
     * Sum order line cost_price for this client in the month (same window as discount sum).
     */
    public function monthlyCostUsed(string $clientId, ?Carbon $at = null): float
    {
        [$start, $end] = $this->monthRangeInAppTimezone($at ?? Carbon::now(config('app.timezone')));

        $sum = DB::table('order_products')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->where('orders.client_id', $clientId)
            ->where('orders.status', '!=', 'returned')
            ->whereBetween(DB::raw('COALESCE(orders.order_date, orders.created_at)'), [$start, $end])
            ->sum(DB::raw('COALESCE(order_products.cost_price, 0)'));

        return round((float) ($sum ?? 0), 2);
    }

    /**
     * Proposed total cost for cart lines (same basis as reviewOrderPrice orderCostPrice).
     *
     * @param  array<int, array{productId: string, price: float|int, quantity: int|float}>  $reviewProducts
     */
    public function proposedOrderCostFromReviewProducts(array $reviewProducts): float
    {
        $sum = 0.0;
        foreach ($reviewProducts as $p) {
            $product = Product::find($p['productId']);
            if (! $product) {
                continue;
            }
            $sum += CalculateProductCostPrice::calculateCostPrice($product) * (float) ($p['quantity'] ?? 0);
        }

        return round($sum, 2);
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function monthRangeInAppTimezone(Carbon $at): array
    {
        $tz = config('app.timezone');
        $local = $at->copy()->timezone($tz);

        return [
            $local->copy()->startOfMonth()->startOfDay(),
            $local->copy()->endOfMonth()->endOfDay(),
        ];
    }

    /**
     * @return array{message: string}|null  Error payload, or null if allowed
     */
    public function validateOrderCreate(array $data): ?array
    {
        $orderClientTypeId = $data['client_type_id'] ?? null;
        if (! $orderClientTypeId) {
            return null;
        }

        $clientId = $this->resolveClientIdForLimitCheck($data);
        $client = $clientId ? Client::find($clientId) : null;

        $limitClientTypeId = $client?->client_type_id ?? $orderClientTypeId;

        $limitClientType = ClientType::find($limitClientTypeId);
        if (! $limitClientType || $limitClientType->monthly_discount_limit === null) {
            return null;
        }

        $limit = round((float) $limitClientType->monthly_discount_limit, 2);

        $products = $this->buildReviewProductsFromStorePayload($data['products'] ?? []);
        if ($products === []) {
            return null;
        }

        $useCostBasis = (bool) $limitClientType->use_cost_basis;

        if ($useCostBasis) {
            $proposed = $this->proposedOrderCostFromReviewProducts($products);
            $used = $clientId ? $this->monthlyCostUsed($clientId) : 0.0;
        } else {
            $preview = $this->orderRepository->reviewOrderPrice([
                'products' => $products,
                'client_id' => $clientId,
                'client_type_id' => $orderClientTypeId,
                'department_id' => $data['department_id'],
            ]);
            $proposed = round((float) ($preview['discount'] ?? 0), 2);
            $used = $clientId ? $this->monthlyDiscountUsed($clientId) : 0.0;
        }

        $remaining = round(max(0, $limit - $used), 2);

        if ($proposed <= $remaining + 0.001) {
            return null;
        }

        if ($useCostBasis) {
            return [
                'message' => 'تكلفة هذا الطلب تتجاوز الحد الشهري المسموح (أساس التكلفة). المتبقي لهذا الشهر: '.$remaining.' (الحد الشهري: '.$limit.'، المستخدم حتى الآن: '.$used.'، تكلفة الطلب المحسوبة: '.$proposed.')',
            ];
        }

        return [
            'message' => 'خصم هذا الطلب يتجاوز الحد الشهري المسموح للخصم. المتبقي لهذا الشهر: '.$remaining.' (الحد الشهري: '.$limit.'، المستخدم حتى الآن: '.$used.'، خصم الطلب المحسوب: '.$proposed.')',
        ];
    }

    /**
     * Snapshot for UI (limit_basis derived from client_types.use_cost_basis when limit applies).
     */
    public function getMonthlyDiscountStatus(array $data): array
    {
        $orderClientTypeId = $data['client_type_id'] ?? null;
        if (! $orderClientTypeId) {
            return [
                'applies' => false,
                'limit' => null,
                'limit_basis' => 'discount',
                'used' => 0.0,
                'remaining' => null,
                'proposed_discount' => 0.0,
                'proposed_cost' => 0.0,
                'would_exceed' => false,
            ];
        }

        $clientId = $this->resolveClientIdForLimitCheck($data);
        $client = $clientId ? Client::find($clientId) : null;

        $limitClientTypeId = $client?->client_type_id ?? $orderClientTypeId;

        $limitClientType = ClientType::find($limitClientTypeId);
        if (! $limitClientType || $limitClientType->monthly_discount_limit === null) {
            return [
                'applies' => false,
                'limit' => null,
                'limit_basis' => 'discount',
                'used' => 0.0,
                'remaining' => null,
                'proposed_discount' => 0.0,
                'proposed_cost' => 0.0,
                'would_exceed' => false,
            ];
        }

        $limit = round((float) $limitClientType->monthly_discount_limit, 2);
        $useCostBasis = (bool) $limitClientType->use_cost_basis;
        $basis = $useCostBasis ? 'cost' : 'discount';

        $products = $this->buildReviewProductsFromStorePayload($data['products'] ?? []);

        $proposedDiscount = 0.0;
        $proposedCost = 0.0;
        if ($products !== []) {
            $proposedCost = $this->proposedOrderCostFromReviewProducts($products);
            $preview = $this->orderRepository->reviewOrderPrice([
                'products' => $products,
                'client_id' => $clientId,
                'client_type_id' => $orderClientTypeId,
                'department_id' => $data['department_id'],
            ]);
            $proposedDiscount = round((float) ($preview['discount'] ?? 0), 2);
        }

        if ($useCostBasis) {
            $used = $clientId ? $this->monthlyCostUsed($clientId) : 0.0;
            $proposed = $proposedCost;
        } else {
            $used = $clientId ? $this->monthlyDiscountUsed($clientId) : 0.0;
            $proposed = $proposedDiscount;
        }

        $remaining = round(max(0, $limit - $used), 2);

        return [
            'applies' => true,
            'limit' => $limit,
            'limit_basis' => $basis,
            'used' => $used,
            'remaining' => $remaining,
            'proposed_discount' => $proposedDiscount,
            'proposed_cost' => $proposedCost,
            'would_exceed' => $proposed > $remaining + 0.001,
        ];
    }
}
