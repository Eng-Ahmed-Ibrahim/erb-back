<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;

class AddPriceToAllOrderProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:product-price';

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
        $products = Product::with('prices')->get();
        foreach ($products as $product) {
            $product->price = (float) $product->prices?->where('default', 1)?->first()?->price ?? 0;
            $product->save();
        }

        $orders = Order::with('products')->get();
        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $product->price = (float) $product->product?->price ?? 0;
                $product->save();
            }
        }
    }
}
