<?php

namespace App\DTOs;

use App\Models\InvoiceRecipe;
use App\Models\Supplier;

class InComingInvoiceDTO
{
    public string $type;

    public ?string $from = null;

    public ?string $to = null;

    public ?string $supplier_id = null;

    public string $invoice_date;

    public string $code;

    public string $discount;

    public string $tax;

    public string $note;

    public array $recipes;

    public function __construct(array $data)
    {
        $this->type = 'in_coming';
        $this->invoice_date = now()->toDateString();
        $this->code = uniqid(strtoupper(substr($this->type, 0, 3)).'-');
        $this->discount = $data['discount'] ?? '0';
        $this->tax = $data['tax'] ?? '0';
        $this->note = $data['note'] ?? 'Auto-generated incoming invoice ';
        $this->recipes = $this->mapRecipes($data['recipes']);
        $this->supplier_id = Supplier::NOZOM_BLANCES_ADJUSTING_SUPPLIER;
        $this->to = $data['to'];
    }

    // Map recipes to the recipes format
    private function mapRecipes(array $recipes): array
    {
        return collect($recipes)->map(function ($item) {
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
            'code' => $this->code,
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
