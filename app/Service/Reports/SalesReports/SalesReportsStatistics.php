<?php

namespace App\Service\Reports\SalesReports;

use App\Models\Order;

class SalesReportsStatistics
{
    public function getSalesReports($departmentID, $productId, $date)
    {
        $ordersOfDepartment = Order::where('department_id', $departmentID)
            ->whereHas('products', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })->get();

        if ($date) {
            $ordersOfDepartment = $ordersOfDepartment->filter(function ($order) use ($date) {
                return $order->create_at >= $date['from'] && $order->create_at <= $date['to'];
            });
        }
        $formatData = $this->formatData($ordersOfDepartment, $productId);

        return $formatData;

    }

    private function formatData($orders, $productId)
    {
        $data = [];
        $totalPrice = 0;
        $totalQuantity = 0;
        foreach ($orders as $order) {
            $data[] = [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'order_date' => $order->order_date,
                'client_name' => $order->client->name ?? null,
                'client_phone' => $order->client->phone ?? null,
                'product_quantity' => $order->products->where('product_id', $productId)->first()->quantity,
                'total_price' => $order->products->first()->price * $order->products->first()->quantity,
            ];
            $totalPrice += $order->products->first()->price * $order->products->first()->quantity;
            $totalQuantity += $order->products->where('product_id', $productId)->first()->quantity;
        }

        return [
            'data' => $data,
            'total_price' => $totalPrice,
            'total_quantity' => $totalQuantity];
    }
}
