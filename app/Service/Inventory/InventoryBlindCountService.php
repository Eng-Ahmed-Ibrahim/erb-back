<?php

namespace App\Service\Inventory;

use App\Models\DepartmentStore;
use App\Models\InventoryBlindCount;
use App\Models\InventoryBlindCountItem;
use App\Models\RecipeQuantity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class InventoryBlindCountService
{
    public function listDepartmentItems(string $departmentId, ?string $search = null): Collection
    {
        return DepartmentStore::query()
            ->with(['recipe.unit', 'recipe.recipeCategory'])
            ->where('department_id', $departmentId)
            ->when($search, fn($query) => $query->whereHas(
                'recipe',
                fn($recipeQuery) => $recipeQuery->where('name', 'like', '%'.$search.'%')
            ))
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function createBlindCount(array $payload, string $cashierId): InventoryBlindCount
    {
        return DB::transaction(function () use ($payload, $cashierId) {
            /** @var \App\Models\InventoryBlindCount $blindCount */
            $blindCount = InventoryBlindCount::create([
                'department_id' => $payload['department_id'],
                'cashier_id' => $cashierId,
                'waiter_old_id' => $payload['waiter_old_id'],
                'waiter_new_id' => $payload['waiter_new_id'],
                'submitted_at' => Carbon::now(),
                'notes' => $payload['notes'] ?? null,
                'status' => 'submitted',
            ]);

            $totalUnder = 0;
            $totalOver = 0;
            $itemsCount = 0;
            $totalFine = 0;
            $recipesForInvoice = [];

            foreach ($payload['items'] as $item) {
                /** @var DepartmentStore $departmentStore */
                $departmentStore = DepartmentStore::query()
                    ->where('department_id', $payload['department_id'])
                    ->where('id', $item['department_store_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $systemQuantity = (float) $departmentStore->quantity;
                $actualQuantity = (float) $item['actual_quantity'];
                $variance = $actualQuantity - $systemQuantity;

                // Determine variance type and calculate fine
                $varianceType = 'over'; // default
                $varianceAbs = abs($variance);

                if (abs($variance) < 0.0001) {
                    // No variance - mark as 'over' with 0 values
                    $variance = 0;
                    $varianceAbs = 0;
                } else {
                    $varianceType = $variance < 0 ? 'under' : 'over';
                }

                $unitCost = $this->resolveUnitCost($departmentStore);
                $fineAmount = $varianceType === 'under' && $varianceAbs > 0
                    ? round($varianceAbs * $unitCost * 1.7, 2)
                    : 0.0;

                InventoryBlindCountItem::create([
                    'inventory_blind_count_id' => $blindCount->id,
                    'department_store_id' => $departmentStore->id,
                    'recipe_id' => $departmentStore->recipe_id,
                    'system_quantity' => $systemQuantity,
                    'actual_quantity' => $actualQuantity,
                    'variance_quantity' => $variance,
                    'variance_type' => $varianceType,
                    'unit_cost' => $unitCost,
                    'fine_amount' => $fineAmount,
                ]);

                // Only update actual_quantity for tracking, don't manipulate under/over
                $departmentStore->update([
                    'actual_quantity' => $actualQuantity,
                ]);

                $itemsCount++;
                if ($varianceType === 'under' && $varianceAbs > 0) {
                    $totalUnder += $varianceAbs;
                    $totalFine += $fineAmount;
                } elseif ($varianceType === 'over' && $varianceAbs > 0) {
                    $totalOver += $varianceAbs;
                }

                // Prepare recipe data for invoice if there's a variance
                if (abs($variance) >= 0.0001) {
                    // Get the latest expire_date from recipe_quantities
                    $latestExpireDate = RecipeQuantity::where('department_store_id', $departmentStore->id)
                        ->where('recipe_id', $departmentStore->recipe_id)
                        ->where('remaining', '>', 0)
                        ->orderBy('expire_date', 'asc')
                        ->value('expire_date');

                    $recipesForInvoice[] = [
                        'recipe_id' => $departmentStore->recipe_id,
                        'quantity' => (float) $variance, // positive for surplus, negative for deficit
                        'price' => $unitCost,
                        'expire_date' => $latestExpireDate ?? now()->addYear()->format('Y-m-d'),
                    ];
                }
            }

            $blindCount->update([
                'items_count' => $itemsCount,
                'total_under_quantity' => $totalUnder,
                'total_over_quantity' => $totalOver,
                'total_fine_amount' => $totalFine,
            ]);

            // Create inventory adjustment invoice if there are variances
            if (!empty($recipesForInvoice)) {
                try {
                    $invoiceData = new \App\DTOs\InventoryAdjustmentInvoiceDTO([
                        'recipes' => $recipesForInvoice,
                        'from' => $blindCount->department_id,
                    ]);

                    $invoice = (new \App\Service\Factory\Invoices\InventoryAdjustmentInvoice)->createInvoice($invoiceData->toArray());

                    if ($invoice && !isset($invoice['status']) || $invoice['status'] !== false) {
                        // Create inventory discrepancy review
                        \App\Http\Controllers\Api\V1\DepartmentInventoryReviewController::createFromInventoryDiscrepancy([
                            'department_id' => $blindCount->department_id,
                            'invoice_id' => $invoice->id,
                            'total_missing_quantity' => abs($blindCount->total_under_quantity),
                            'estimated_loss_amount' => $blindCount->total_fine_amount,
                            'discrepancy_note' => $blindCount->notes ?? 'جرد المخزون الأعمى - رقم: ' . $blindCount->id,
                            'cashier_id' => $blindCount->cashier_id,
                            'waiter_id' => $blindCount->waiter_old_id,
                        ]);

                        // Update blind count with invoice reference and auto-approve
                        $blindCount->update([
                            'invoice_id' => $invoice->id,
                            'status' => 'approved',
                            'approved_by' => $cashierId,
                            'approved_at' => Carbon::now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to create adjustment invoice for blind count', [
                        'blind_count_id' => $blindCount->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue without invoice - it can be created during approval
                }
            }

            try {
                $pdfPath = $this->generatePdf($blindCount->fresh('items.recipe', 'department', 'cashier', 'waiterOld', 'waiterNew'));
                $blindCount->update(['pdf_path' => $pdfPath]);
            } catch (\Exception $e) {
                \Log::warning('Failed to generate blind count PDF', [
                    'blind_count_id' => $blindCount->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue without PDF - it can be generated later
            }

            return $blindCount->fresh('items.recipe', 'department', 'cashier', 'waiterOld', 'waiterNew');
        });
    }

    public function generatePdf(InventoryBlindCount $blindCount): string
    {
        $blindCount->loadMissing('items.recipe', 'department', 'cashier', 'waiterOld', 'waiterNew');

        /** @var \Mccarlosen\LaravelMpdf\LaravelMpdfWrapper $pdf */
        $storageDisk = Storage::disk('public');
        $directory = 'inventory-blind-counts';

        if (! $storageDisk->exists($directory)) {
            $storageDisk->makeDirectory($directory);
        }

        $pdf = PDF::loadView('pdf.inventory.blind_count', [
            'blindCount' => $blindCount,
        ]);

        $fileName = Str::slug($blindCount->id.'-'.$blindCount->submitted_at?->format('Ymd_His') ?? now()->format('Ymd_His')).'.pdf';
        $path = $directory.'/'.$fileName;

        $content = $pdf->download($fileName)->getOriginalContent();
        $storageDisk->put($path, $content);

        return $path;
    }

    public function approveBlindCount(InventoryBlindCount $blindCount, string $approverId): array
    {
        if ($blindCount->status === 'approved') {
            throw new \RuntimeException('تم الموافقة على هذا الجرد مسبقاً.');
        }

        return DB::transaction(function () use ($blindCount, $approverId) {
            $blindCount->loadMissing('items.departmentStore.recipe', 'department');

            // Prepare recipes for inventory adjustment
            $recipes = $blindCount->items->map(function ($item) {
                if ($item->variance_quantity == 0) {
                    return null;
                }

                // Get the latest expire_date from recipe_quantities for this department_store
                $latestExpireDate = RecipeQuantity::where('department_store_id', $item->department_store_id)
                    ->where('recipe_id', $item->departmentStore->recipe_id)
                    ->where('remaining', '>', 0)
                    ->orderBy('expire_date', 'asc')
                    ->value('expire_date');

                return [
                    'recipe_id' => $item->departmentStore->recipe_id,
                    'quantity' => (float) $item->variance_quantity, // positive for surplus, negative for deficit
                    'price' => $item->unit_cost ?? 0,
                    'expire_date' => $latestExpireDate ?? now()->addYear()->format('Y-m-d'), // Default to 1 year from now if no expire date found
                ];
            })->filter()->values()->toArray();

            if (empty($recipes)) {
                $blindCount->update([
                    'status' => 'approved',
                    'approved_by' => $approverId,
                    'approved_at' => Carbon::now(),
                ]);

                return [
                    'status' => true,
                    'message' => 'تم الموافقة على الجرد بنجاح (لا توجد فروقات تتطلب تسوية).',
                    'invoice' => null,
                ];
            }

            // Create inventory adjustment invoice DTO
            $invoiceData = new \App\DTOs\InventoryAdjustmentInvoiceDTO([
                'recipes' => $recipes,
                'from' => $blindCount->department_id,
            ]);

            // Create the invoice
            $invoice = (new \App\Service\Factory\Invoices\InventoryAdjustmentInvoice)->createInvoice($invoiceData->toArray());

            if (!$invoice['status']) {
                throw new \RuntimeException($invoice['message'] ?? 'فشل في إنشاء فاتورة التسوية.');
            }

            // Create inventory discrepancy review
            \App\Http\Controllers\Api\V1\DepartmentInventoryReviewController::createFromInventoryDiscrepancy([
                'department_id' => $blindCount->department_id,
                'invoice_id' => $invoice->id,
                'total_missing_quantity' => abs($blindCount->total_under_quantity),
                'estimated_loss_amount' => $blindCount->total_fine_amount,
                'discrepancy_note' => $blindCount->notes ?? 'تسوية من جرد المخزون الأعمى - رقم: ' . $blindCount->id,
                'cashier_id' => $blindCount->cashier_id,
                'waiter_id' => $blindCount->waiter_old_id,
            ]);

            // Update blind count status
            $blindCount->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => Carbon::now(),
                'invoice_id' => $invoice->id,
            ]);

            return [
                'status' => true,
                'message' => 'تم الموافقة على الجرد وإنشاء فاتورة التسوية بنجاح.',
                'invoice' => $invoice,
            ];
        });
    }

    protected function resolveUnitCost(DepartmentStore $departmentStore): float
    {
        $latestPrice = RecipeQuantity::query()
            ->where('department_store_id', $departmentStore->id)
            ->orderByDesc('created_at')
            ->value('price');

        if ($latestPrice !== null) {
            return (float) $latestPrice;
        }

        if ($departmentStore->quantity > 0 && $departmentStore->price > 0) {
            return (float) ($departmentStore->price / $departmentStore->quantity);
        }

        return 0.0;
    }
}

