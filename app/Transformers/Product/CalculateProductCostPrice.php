<?php

namespace App\Transformers\Product;

class CalculateProductCostPrice
{
    public static function calculateCostPrice($product)
    {
        $costPrice = 0;
        $counter = 0;
        foreach ($product->recipes as $recipe) {
            $recipeInvoices = $recipe->invoices()->where('type', 'in_coming')->where('invoices.total_price', '!=', 0)
                ->where('supplier_id', '!=', '01jd7s43xfkbtp8vj9z35ksh7d')->latest()->limit(1)->get();
            foreach ($recipeInvoices as $invoice) {
                $counter++;
                $costPrice += $recipe->pivot->quantity * $invoice->pivot->price;
            }
            if ($counter == 0) {
                $counter = 1;
            }
            $costPrice = $costPrice / $counter;
            $counter = 0;
        }

        return $costPrice;
    }
}
