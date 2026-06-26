<?php

namespace App\Transformers\Waiter;

use App\Models\Waiter;
use App\Transformers\BaseTransformer;
use App\Transformers\Order\AbstractOrderTransformer;
use Carbon\Carbon;

class WaiterTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @return array
     */
    public static function transform(Waiter $waiter)
    {
        $formatedOrders = [];
        $formatedYesterDayOrders = [];

        if (auth()->user()->department->type == 'master') {
            $todayOrders = $waiter->orders()
                ->where('created_at', '>=', now()->format('Y-m-d'))
                ->get();

            $yesterdayOrders = $waiter->orders()
                ->where('created_at', '>=', Carbon::yesterday()
                    ->where('created_at', '<', now()->format('Y-m-d'))
                    ->format('Y-m-d'))->get();
        } else {
            $todayOrders = $waiter->orders()
                ->where('created_at', '>=', now()->format('Y-m-d'))
                ->where('department_id', auth()->user()->department_id)
                ->get();

            $yesterdayOrders = $waiter->orders()
                ->where('created_at', '>=', Carbon::yesterday()->format('Y-m-d'))
                ->where('created_at', '<', now()->format('Y-m-d'))
                ->where('department_id', auth()->user()->department_id)
                ->get();
        }

        $totalsToday = [
            'total_visa' => $todayOrders->where('payment_method', 'visa')->sum('total_price'),
            'total_cash' => $todayOrders->where('payment_method', 'cash')->sum('total_price'),
            'total_post_paid' => $todayOrders->where('payment_method', 'postpaid')->sum('total_price'),
            'total_hospitality' => $todayOrders->where('payment_method', 'hospitality')->sum('total_price'),
            'total' => $todayOrders->sum('total_price'),
        ];

        $totalYesterday = [
            'total_visa' => $yesterdayOrders->where('payment_method', 'visa')->sum('total_price'),
            'total_cash' => $yesterdayOrders->where('payment_method', 'cash')->sum('total_price'),
            'total_post_paid' => $yesterdayOrders->where('payment_method', 'postpaid')->sum('total_price'),
            'total_hospitality' => $yesterdayOrders->where('payment_method', 'hospitality')->sum('total_price'),
            'total' => $yesterdayOrders->sum('total_price'),
        ];

        foreach ($todayOrders as $order) {
            $formatedOrders[] = AbstractOrderTransformer::transform($order);
        }

        foreach ($yesterdayOrders as $yesterdayOrder) {
            $formatedYesterDayOrders = [];
            $formatedYesterDayOrders[] = AbstractOrderTransformer::transform($yesterdayOrder);

        }

        return [
            'id' => (string) $waiter->id,
            'name' => $waiter->name,
            'image' => $waiter->image ? (string) config('app.url').$waiter->image : '',
            'phone' => $waiter->phone,
            'total_today' => $waiter->orders->where('created_at', '>=', now()->format('Y-m-d'))->sum('total_price'),
            'number_of_orders_today' => $waiter->orders->where('created_at', '>=', now()->format('Y-m-d'))->count(),
            'orders_today' => $formatedOrders,
            'details_for_yesterday' => $totalYesterday,
            'details_for_today' => $totalsToday,
            'total_yesterday' => $waiter->orders->where('created_at', '>=', Carbon::yesterday()->format('Y-m-d'))->sum('total_price'),
            'number_of_orders_yesterday' => $waiter->orders->where('created_at', '>=', Carbon::yesterday()->format('Y-m-d'))->count(),
            'orders_yesterday' => $formatedYesterDayOrders,
        ];
    }
}
