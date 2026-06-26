<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceRecipe;
use App\Models\RecipeQuantity;
use App\Models\DepartmentStore;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\Employee;
use App\Models\OrderProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearRecipesBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-data';

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
       
        Invoice::query()->delete();
        InvoiceRecipe::query()->delete();
        RecipeQuantity::query()->delete();
        DepartmentStore::query()->delete();
        Supplier::query()->delete();
        Order::query()->delete();
        Employee::query()->delete();


    }
}
