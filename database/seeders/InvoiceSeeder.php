<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Invoice::factory()->count(1000)->create()->each(function ($invoice) {

            $recipeIds = Recipe::pluck('id')->random(mt_rand(1, 5));
            foreach ($recipeIds as $recipeId) {
                $invoice->recipes()->attach($recipeId, [
                    'quantity' => mt_rand(1, 10),
                    'price' => mt_rand(1000, 10000),
                    'expire_date' => now()->addDays(mt_rand(1, 30))->format('Y-m-d'),
                    'total_price' => mt_rand(1000, 10000) * mt_rand(1, 10),
                    'status' => 'active',
                ]);
            }
        });
    }
}
