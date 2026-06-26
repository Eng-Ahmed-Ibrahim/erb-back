<?php

namespace App\DTOs;

use App\Models\InvoiceRecipe;

class InventoryAdjustmentInvoiceDTO
{
    public string $type;

    public ?string $from = null;

    public ?string $to = null;

    public ?string $supplier_id = null;

    public string $invoice_date;

    public string $discount;

    public string $tax;

    public string $note;

    public array $recipes;

    public function __construct(array $data)
    {
        $this->type = 'inventory_adjustment';
        $this->invoice_date = now()->toDateString();

        $this->discount = $data['discount'] ?? '0';
        $this->tax = $data['tax'] ?? '0';
        $this->note = $data['note'] ?? 'Auto-generated invoice for adjusting the inventory balance';
        $this->recipes = $this->mapRecipes($data['recipes']);

        $this->from = $data['from'];
    }

    // Map items to the recipes format
    private function mapRecipes(array $items): array
    {
        return collect($items)->map(function ($item) {
            return [
                'recipe_id' => $item['recipe_id'],
                'quantity' => $item['quantity'] ?? 0,
                'price' => $this->getRecipeLastInvoicePrice($item['recipe_id']),
                'expire_date' => $item['expire_date'] ?? now()->addMonths(6)->toDateString(),
            ];
        })->toArray();
    }

    // Return the DTO data as an array
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'from' => $this->from,
            'to' => $this->to,
            'supplier_id' => $this->supplier_id,
            'invoice_date' => $this->invoice_date,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'note' => $this->note,
            'recipes' => $this->recipes,
        ];
    }

    private function getRecipeLastInvoicePrice($recipe_id): ?float
    {
        if (! $recipe_id) {
            return 0;
        }
        
        $price = InvoiceRecipe::query()
            ->where('recipe_id', $recipe_id)
            ->where('price', '!=', 0)
            ->latest('created_at', 'desc')
            ->value('price');

        return $price !== null ? (float) $price : 0;
    }
}
