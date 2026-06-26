<?php

namespace App\Console\Commands;

use App\Jobs\PushOrderNotificationJob;
use App\Models\Order;
use Illuminate\Console\Command;

class sendWhatsAppMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-whats-app-message-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $orders = Order::query()
        // ->where("client_id",'!=', '01k5ff4yygfkvnpdkdtqpknvw5')
        ->where("client_type_id", '25621e6e-dbb2-4524-91ab-2acf699e9e97')->where('created_at','>=','2025-09-27 01:00:00')->get();
        foreach ($orders as $order) {
            PushOrderNotificationJob::dispatch(Order::query()->where('code', $order->code)->first());
        }
    }
}
