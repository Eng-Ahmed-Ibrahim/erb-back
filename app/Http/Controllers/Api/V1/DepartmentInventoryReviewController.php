<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DepartmentInventoryReview;
use Illuminate\Http\Request;
use App\Transformers\DepartmentInventoryReviewTransformer;
use Illuminate\Http\Response;

class DepartmentInventoryReviewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = DepartmentInventoryReview::query()
            
            ->when(isset($request->department_id), fn($query) => $query->where('department_id', $request->department_id))
            ->when(isset($request->from_date), fn($query) => $query->whereDate('created_at', '>=', $request->from_date))
            ->when(isset($request->department_id), fn($query) => $query->whereDate('created_at', '<=', $request->to_date))
            ->orderBy('created_at', 'desc');

        $reviews = $query->get();

        return responder()->success($reviews, DepartmentInventoryReviewTransformer::Class)->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'cashier_id' => 'required|exists:users,id',
            'waiter_id' => 'required|exists:users,id',
            'discrepancy_note' => 'nullable|string',
            'total_missing_quantity' => 'required|numeric|min:0',
            'estimated_loss_amount' => 'required|numeric|min:0',
        ]);

        $review = DepartmentInventoryReview::create([
            'department_id' => $request->department_id,
            'cashier_id' => $request->cashier_id,
            'waiter_id' => $request->waiter_id,
            'discrepancy_note' => $request->discrepancy_note,
            'total_missing_quantity' => $request->total_missing_quantity,
            'estimated_loss_amount' => $request->estimated_loss_amount,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'status' => 'pending',
        ]);

        return responder()->success($review)->respond(Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $review = DepartmentInventoryReview::findOrFail($id);

        $request->validate([
            'discrepancy_note' => 'nullable|string',
            'total_missing_quantity' => 'required|numeric|min:0',
            'estimated_loss_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,resolved,dismissed',
        ]);

        $review->update([
            'discrepancy_note' => $request->discrepancy_note,
            'total_missing_quantity' => $request->total_missing_quantity,
            'estimated_loss_amount' => $request->estimated_loss_amount,
            'status' => $request->status,
        ]);

        return responder()->success($review)->respond();
    }

    public function destroy($id)
    {
        $review = DepartmentInventoryReview::findOrFail($id);
        $review->delete();

        return responder()->success(['message' => 'تم حذف المراجعة بنجاح'])->respond();
    }

    public static function createFromInventoryDiscrepancy(array $data)
    {
        return DepartmentInventoryReview::create([
            'department_id' => $data['department_id'],
            'cashier_id' => $data['cashier_id'],
            'waiter_id' => $data['waiter_id'],
            'invoice_id' => $data['invoice_id'],
            'discrepancy_note' => $data['discrepancy_note'] ?? null,
            'total_missing_quantity' => $data['total_missing_quantity'],
            'estimated_loss_amount' => $data['estimated_loss_amount'],
            'reviewed_by' => null,
            'reviewed_at' => now(),
            'status' => 'pending',
        ]);
    }
}
