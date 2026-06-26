<?php

namespace App\Jobs;

use App\Models\ClientType;
use App\Models\Order;
use App\Service\Integrations\RabbitMQService;
use App\Transformers\Product\AbstractProductOrderTransformer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->order->client_type_id == ClientType::DEPARTMENT_MANAGER_ID && isset($this->order->client?->phone)) {
            $rabbitMQ = new RabbitMQService;
            $rabbitMQ->sendMessage([
                'code' => $this->order->code,
                'price' => $this->order->price,
                'total_price' => $this->order->total_price,
                'products' => $this->order->products->map(function ($product) {
                    return AbstractProductOrderTransformer::transform($product->product, $product->order_id);
                })->toArray(),
                'discount' => max($this->order->price - $this->order->total_price, 0),
                'tax' => $this->order->tax,
                'phone_number' => $this->order->client->phone,
                'department' => $this->order->department->name,
                'at' => Carbon::parse($this->order->created_at)->format('Y-m-d h:i:s'),
                'cashier' => $this->order->user->name,
                'waiter' => $this->order->waiter->name,
                'payment_method' => $this->order->payment_method,
            ]);
        }
    }
}
