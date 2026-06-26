<?php

namespace App\Console\Commands;

use App\Models\DepartmentStore;
use App\Service\Factory\Invoices\InComingInvoice;
use Illuminate\Console\Command;

class FixTainedInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix-tained-invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Join the Recipe table to get the product price for each recipe
        $recipes = DepartmentStore::where('department_id', '01hy3km07mf7fafqn2j6388d1t')
            ->join('recipes', 'department_store.recipe_id', '=', 'recipes.id')  // Adjust the column names if necessary
            ->select('department_store.recipe_id as recipe_id')
            ->get()
            ->map(function ($recipe) {
                return [
                    'recipe_id' => $recipe->recipe_id,
                    'quantity' => '0',  // Set a default or specific quantity if needed
                    'price' => 0,  // Use the price from the Recipe table
                    'expire_date' => '2025-12-07',  // Set default or specific expire date if needed
                ];
            });

        // Construct the main $data array
        $data = [
            'type' => 'in_coming',
            'supplier_id' => '01jbacxsqzcp6e23vryy75pdb5',
            'invoice_date' => '2024-11-05',
            'image' => 'Illuminate\Http\UploadedFile":"C:\Users\B-End\AppData\Local\Temp\php687D.tmp',
            'discount' => '0',
            'tax' => '0',
            'note' => null,
            'code' => '159888951',
            'recipes' => $recipes->toArray(),
        ];

        // Now create the invoice with the data

        $invoice = (new InComingInvoice)->createInvoice($data);
        if (! $invoice['status']) {
        }
    }
}
