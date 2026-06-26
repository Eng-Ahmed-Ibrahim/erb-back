<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryBlindCount\StoreInventoryBlindCountRequest;
use App\Models\InventoryBlindCount;
use App\Service\Inventory\InventoryBlindCountService;
use App\Transformers\Inventory\InventoryBlindCountTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InventoryBlindCountController extends Controller
{
    public function __construct(private readonly InventoryBlindCountService $inventoryBlindCountService)
    {
    }

    public function listItems(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'search' => 'nullable|string|max:255',
        ]);

        $departmentId = $validated['department_id'] ?? auth()->user()?->department_id;

        if (! $departmentId) {
            return responder()
                ->error('invalid_department', 'لا يمكن تحديد القسم المطلوب للجرد.')
                ->respond(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $items = $this->inventoryBlindCountService->listDepartmentItems(
            $departmentId,
            $validated['search'] ?? null
        )->map(function ($departmentStore) {
            return [
                'department_store_id' => (string) $departmentStore->id,
                'recipe_id' => (string) $departmentStore->recipe_id,
                'name' => $departmentStore->recipe?->name,
                'unit' => $departmentStore->recipe?->unit?->name,
                'category' => $departmentStore->recipe?->recipeCategory?->name,
                'image' => $departmentStore->recipe?->image ? (string) config('app.url').$departmentStore->recipe?->image : null,
            ];
        });

        return responder()->success([
            'department_id' => (string) $departmentId,
            'items' => $items,
        ])->respond(Response::HTTP_OK);
    }

    public function store(StoreInventoryBlindCountRequest $request)
    {
        try {
            $blindCount = $this->inventoryBlindCountService->createBlindCount(
                $request->validated(),
                auth()->id()
            );
        } catch (\RuntimeException $exception) {
            return responder()
                ->error('invalid_submission', $exception->getMessage())
                ->respond(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return responder()
            ->success($blindCount, InventoryBlindCountTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'cashier_id' => 'nullable|exists:users,id',
            'waiter_id' => 'nullable|exists:waiters,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'variance_type' => 'nullable|in:under,over',
        ]);

        $blindCounts = InventoryBlindCount::query()
            ->with(['department', 'cashier', 'waiterOld', 'waiterNew', 'items.recipe.unit'])
            ->when(
                $validated['department_id'] ?? null,
                fn($query, $departmentId) => $query->where('department_id', $departmentId)
            )
            ->when(
                $validated['cashier_id'] ?? null,
                fn($query, $cashierId) => $query->where('cashier_id', $cashierId)
            )
            ->when(
                $validated['waiter_id'] ?? null,
                fn($query, $waiterId) => $query->where(function ($nested) use ($waiterId) {
                    $nested
                        ->where('waiter_old_id', $waiterId)
                        ->orWhere('waiter_new_id', $waiterId);
                })
            )
            ->when(
                $validated['from_date'] ?? null,
                fn($query, $from) => $query->whereDate('submitted_at', '>=', $from)
            )
            ->when(
                $validated['to_date'] ?? null,
                fn($query, $to) => $query->whereDate('submitted_at', '<=', $to)
            )
            ->when(
                $validated['variance_type'] ?? null,
                fn($query, $varianceType) => $query->whereHas(
                    'items',
                    fn($itemQuery) => $itemQuery->where('variance_type', $varianceType)
                )
            )
            ->orderByDesc('submitted_at')
            ->get();

        return responder()
            ->success($blindCounts, InventoryBlindCountTransformer::class)
            ->respond(Response::HTTP_OK);
    }

    public function show(InventoryBlindCount $inventoryBlindCount)
    {
        $inventoryBlindCount->loadMissing('department', 'cashier', 'waiterOld', 'waiterNew', 'items.recipe.unit');

        return responder()
            ->success($inventoryBlindCount, InventoryBlindCountTransformer::class)
            ->respond(Response::HTTP_OK);
    }

    public function download(InventoryBlindCount $inventoryBlindCount)
    {
        if (! $inventoryBlindCount->pdf_path || ! Storage::disk('public')->exists($inventoryBlindCount->pdf_path)) {
            return responder()
                ->error('missing_pdf', 'ملف الـ PDF غير متوفر لهذا الجرد.')
                ->respond(Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('public')->download($inventoryBlindCount->pdf_path);
    }

    public function approve(InventoryBlindCount $inventoryBlindCount)
    {
        try {
            $result = $this->inventoryBlindCountService->approveBlindCount(
                $inventoryBlindCount,
                auth()->id()
            );

            if (!$result['status']) {
                return responder()
                    ->error('approval_failed', $result['message'])
                    ->respond(Response::HTTP_BAD_REQUEST);
            }

            $freshBlindCount = $inventoryBlindCount->fresh(['department', 'cashier', 'waiterOld', 'waiterNew', 'items.recipe', 'approver', 'invoice']);

            return responder()
                ->success([
                    'message' => $result['message'],
                    'blind_count' => InventoryBlindCountTransformer::transform($freshBlindCount),
                    'invoice_id' => $result['invoice']?->id,
                ])
                ->respond(Response::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return responder()
                ->error('approval_error', $exception->getMessage())
                ->respond(Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $exception) {
            \Log::error('Blind count approval failed', [
                'blind_count_id' => $inventoryBlindCount->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return responder()
                ->error('server_error', 'حدث خطأ أثناء الموافقة على الجرد.')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

