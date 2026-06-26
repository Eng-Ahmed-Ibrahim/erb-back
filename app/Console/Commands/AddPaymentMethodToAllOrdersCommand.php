<?php

namespace App\Console\Commands;

use App\Jobs\AddPaymentMethodToAllOrdersJob;
use App\Models\Order;
use Illuminate\Console\Command;

class AddPaymentMethodToAllOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:client-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to add payment method to all orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::all();
        foreach ($orders as $order) {
            $order->client_type_id = $order->client?->client_type_id;
            $order->save();
        }

        // AddPaymentMethodToAllOrdersJob::dispatch();
    }
}
