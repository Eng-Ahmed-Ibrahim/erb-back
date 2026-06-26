<?php

namespace App\Transformers\Inventory;

use App\Models\InventoryBlindCount;
use App\Transformers\BaseTransformer;
use Illuminate\Support\Facades\Storage;

class InventoryBlindCountTransformer extends BaseTransformer
{
    /**
     * Transform the model.
     *
     * @return array<string, mixed>
     */
    public static function transform(InventoryBlindCount $blindCount): array
    {
        return [
            'id' => (string) $blindCount->id,
            'department' => [
                'id' => (string) $blindCount->department_id,
                'name' => $blindCount->department?->name,
            ],
            'cashier' => [
                'id' => (string) $blindCount->cashier_id,
                'name' => $blindCount->cashier?->name,
            ],
            'waiter_old' => [
                'id' => (string) $blindCount->waiter_old_id,
                'name' => $blindCount->waiterOld?->name,
            ],
            'waiter_new' => [
                'id' => (string) $blindCount->waiter_new_id,
                'name' => $blindCount->waiterNew?->name,
            ],
            'submitted_at' => optional($blindCount->submitted_at)->format('Y-m-d H:i'),
            'items_count' => (int) $blindCount->items_count,
            'total_under_quantity' => (float) $blindCount->total_under_quantity,
            'total_over_quantity' => (float) $blindCount->total_over_quantity,
            'total_fine_amount' => (float) $blindCount->total_fine_amount,
            'status' => $blindCount->status,
            'notes' => $blindCount->notes,
            'pdf_path' => $blindCount->pdf_path,
            'pdf_url' => $blindCount->pdf_path
                ? url(Storage::disk('public')->url($blindCount->pdf_path))
                : null,
            'approved_by' => $blindCount->approved_by ? [
                'id' => (string) $blindCount->approved_by,
                'name' => $blindCount->approver?->name,
            ] : null,
            'approved_at' => optional($blindCount->approved_at)->format('Y-m-d H:i'),
            'invoice_id' => $blindCount->invoice_id ? (string) $blindCount->invoice_id : null,
            'items' => BaseTransformer::formatMany(
                $blindCount->items ?? collect(),
                InventoryBlindCountItemTransformer::class
            ),
        ];
    }
}



