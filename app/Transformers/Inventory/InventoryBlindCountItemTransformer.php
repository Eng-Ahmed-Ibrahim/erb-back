<?php

namespace App\Transformers\Inventory;

use App\Models\InventoryBlindCountItem;
use App\Transformers\BaseTransformer;

class InventoryBlindCountItemTransformer extends BaseTransformer
{
    /**
     * Transform the model.
     *
     * @return array<string, mixed>
     */
    public static function transform(InventoryBlindCountItem $item): array
    {
        return [
            'id' => (string) $item->id,
            'recipe_id' => (string) $item->recipe_id,
            'recipe_name' => $item->recipe?->name,
            'unit' => $item->recipe?->unit?->name,
            'system_quantity' => (float) $item->system_quantity,
            'actual_quantity' => (float) $item->actual_quantity,
            'variance_quantity' => (float) $item->variance_quantity,
            'variance_type' => $item->variance_type,
            'unit_cost' => (float) $item->unit_cost,
            'fine_amount' => (float) $item->fine_amount,
            'notes' => $item->notes,
        ];
    }
}



