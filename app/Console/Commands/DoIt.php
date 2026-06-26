<?php

namespace App\Console\Commands;

use App\Models\DepartmentProduct;
use App\Models\Invoice;
use App\Models\InvoiceRecipe;
use App\Service\Factory\InvoiceFactory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoIt extends Command
{
    protected $signature = 'app:do-it';

    protected $description = 'Create transfer invoice from wrong outgoing invoice';

    public function handle()
    {
        // Configuration
        $wrongOutgoingInvoiceId = '01k91t0wzd4jv1dx9ybfme678j'; // Replace with actual invoice ID
        $correctDestinationDepartmentId = '01k90224am4www6qvg3zdr49nr'; // Replace with correct department ID

        DB::beginTransaction();

        try {
            // Get the wrong outgoing invoice
            $wrongInvoice = Invoice::with(['recipes'])->findOrFail($wrongOutgoingInvoiceId);

            if ($wrongInvoice->type !== 'out_going') {
                $this->error('The invoice is not an outgoing invoice!');
                return 1;
            }

           
            // Prepare transfer invoice data
            $transferData = [
                'from' => $wrongInvoice->to, // From the wrong department
                'to' => $correctDestinationDepartmentId, // To the correct department
                'code' => $wrongInvoice->code,
                'type' => 'transfare',
                'invoice_date' => $wrongInvoice->invoice_date,
                'created_by' => $wrongInvoice->created_by,
                'recipes' => [],
            ];


           
            // Add all recipes from the wrong invoice
            foreach ($wrongInvoice->recipes as $recipe) {
                $transferData['recipes'][] = [
                    'recipe_id' => $recipe->pivot->recipe_id,
                    'quantity' => $recipe->pivot->quantity,
                    'price' => $recipe->pivot->price,
                    'total_price' => $recipe->pivot->total_price,
                    'expire_date' => $recipe->pivot->expire_date
                ];

                $this->info("Recipe: {$recipe->name}, Quantity: {$recipe->pivot->quantity}");
            }

            // Create the transfer invoice
            // dd($transferData);
            $transferInvoice = InvoiceFactory::invoiceBasedOnType('transfare')->createInvoice($transferData);

        
            DB::commit();

            $this->info("\n✅ Transaction committed successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}

