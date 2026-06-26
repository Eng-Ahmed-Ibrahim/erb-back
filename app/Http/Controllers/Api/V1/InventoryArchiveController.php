<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvertoryArchiveRequest;
use App\Models\Department;
use App\Models\InventoryArchive;
use App\Models\InvoiceRecipe;
use Carbon\carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InventoryArchiveController extends Controller
{
    public function index(InvertoryArchiveRequest $request)
    {
        $request->validate([
            'data.from' => 'required|date',
            'data.to' => 'required|date',
            'data.department_id' => 'required',
            'data.name' => 'nullable',
            'data.category_id' => 'nullable',
            'data.report_type' => 'nullable',
        ]);

        $data = $request->input('data', []);
        $result = [];

        $department = Department::find($data['department_id']);

        $latestCaptures = DB::table('inventory_archive')
            ->select('inventory_archive.recipe_id', DB::raw('MAX(captured_at) as captured_at'))
            ->where('inventory_archive.captured_at', '<', $data['from'])
            ->where('department_id', $data['department_id'])
            ->groupBy('inventory_archive.recipe_id');

        $initialStock = InventoryArchive::joinSub($latestCaptures, 'latest', function ($join) {
            $join
                ->on('inventory_archive.recipe_id', '=', 'latest.recipe_id')
                ->on('inventory_archive.captured_at', '=', 'latest.captured_at');
        })
            ->join('recipes', 'recipes.id', '=', 'inventory_archive.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->where('inventory_archive.captured_at', '<', $data['from'])
            ->where('department_id', $data['department_id'])
            ->select('inventory_archive.recipe_id', 'quantity', 'price', 'inventory_archive.captured_at', 'recipes.name')
            // ->whereIn('captured_at', function ($query) use ($data) {
            //     $query
            //         ->selectRaw('MAX(captured_at)')
            //         ->from('inventory_archive')
            //         ->where('captured_at', '<', $data['from'])
            //         ->where('department_id', $data['department_id'])
            //         ->groupBy('recipe_id');
            // })
            ->when(isset($data['category_id']) && $data['category_id'] != '', fn($query) => $query
                ->where('recipe_categories.category_id', $data['category_id']))
            ->when(isset($data['warehouse_section_id']) && $data['warehouse_section_id'] != '', fn($query) => $query
                ->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section_id']))
            ->when(isset($data['name']) && $data['name'] != '', fn($query) => $query
                ->whereRaw('recipes.name LIKE ?', ['%' . $data['name'] . '%']))
            ->get();

        if ($initialStock->count() != 0) {
            $data['from'] = $initialStock[0]['captured_at'];
            $data['to'] = carbon::parse($data['to'])->endOfDay();
        }

        $totalIncoming = InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->when($department->type == 'source', fn($query) => $query
                ->where('invoices.type', 'in_coming'))
            ->when($department->type != 'source', fn($query) => $query
                ->where('invoices.type', 'out_going')
                ->where('invoices.to', $department->id))
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->groupBy('invoice_recipe.recipe_id')
            ->when(isset($data['report_type']) && $data['report_type'] == 2, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.total_price) as total_incoming')))
            ->when(!isset($data['report_type']) || $data['report_type'] == null || $data['report_type'] == 1, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.quantity) as total_incoming')))
            ->get();

        $totalOutgoing = InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('invoices.type', 'out_going')
            ->where('invoices.from', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->distinct()
            ->groupBy('invoice_recipe.recipe_id')
            ->when(isset($data['report_type']) && $data['report_type'] == 2, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.total_price) as total_outgoing')))
            ->when(!isset($data['report_type']) || $data['report_type'] == null || $data['report_type'] == 1, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.quantity) as total_outgoing')))
            ->get();

        $returnedFrom = InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('invoices.type', 'returned')
            ->where('invoices.from', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->distinct()
            ->groupBy('invoice_recipe.recipe_id')
            ->when(isset($data['report_type']) && $data['report_type'] == 2, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.total_price) as total_returned_from')))
            ->when(!isset($data['report_type']) || $data['report_type'] == null || $data['report_type'] == 1, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.quantity) as total_returned_from')))
            ->get();

        $returnedTo = InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('invoices.type', 'returned')
            ->where('invoices.to', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->distinct()
            ->groupBy('invoice_recipe.recipe_id')
            ->when(isset($data['report_type']) && $data['report_type'] == 2, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.total_price) as total_returned_to')))
            ->when(!isset($data['report_type']) || $data['report_type'] == null || $data['report_type'] == 1, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.quantity) as total_returned_to')))
            ->get();

        $totalTainted = InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('invoices.type', 'tainted')
            ->where('invoices.from', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->groupBy('invoice_recipe.recipe_id')
            ->when(isset($data['report_type']) && $data['report_type'] == 2, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.total_price) as total_tainted')))
            ->when(!isset($data['report_type']) || $data['report_type'] == null || $data['report_type'] == 1, fn($query) => $query
                ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.quantity) as total_tainted')))
            ->get();

        foreach ($initialStock as $stock) {
            $initialValue = (isset($data['report_type']) && $data['report_type'] == 2)
                ? $stock->price
                : $stock->quantity;
            $result[$stock->recipe_id] = [
                'recipe_name' => $stock->name,
                'recipe_id' => $stock->recipe_id,
                'initial_stock' => number_format($initialValue, 3, '.', ''),
                'total_incoming' => 0,
                'total_outgoing' => 0,
                'total_returned_to' => 0,
                'total_returned_from' => 0,
                'total_tainted' => 0,
                'total' => 0,
            ];
        }

        foreach ($totalIncoming as $incoming) {
            if (isset($result[$incoming->recipe_id])) {
                $result[$incoming->recipe_id]['total_incoming'] = number_format($incoming->total_incoming, 3, '.', '');
            }
        }

        foreach ($totalOutgoing as $outgoing) {
            if (isset($result[$outgoing->recipe_id])) {
                $result[$outgoing->recipe_id]['total_outgoing'] = number_format($outgoing->total_outgoing, 3, '.', '');
            }
        }

        foreach ($returnedFrom as $returned) {
            if (isset($result[$returned->recipe_id])) {
                $result[$returned->recipe_id]['total_returned_from'] = number_format($returned->total_returned_from, 3, '.', '');
            }
        }

        foreach ($returnedTo as $returned) {
            if (isset($result[$returned->recipe_id])) {
                $result[$returned->recipe_id]['total_returned_to'] = number_format($returned->total_returned_to, 3, '.', '');
            }
        }

        foreach ($totalTainted as $tainted) {
            if (isset($result[$tainted->recipe_id])) {
                $result[$tainted->recipe_id]['total_tainted'] = number_format($tainted->total_tainted ?? 0, 3, '.', '');
            }
        }

        foreach ($result as $recipe) {
            $result[$recipe['recipe_id']]['total'] = number_format($recipe['initial_stock']
                + $recipe['total_incoming']
                - $recipe['total_outgoing']
                + $recipe['total_returned_to']
                - $recipe['total_returned_from']
                - $recipe['total_tainted'], 3, '.', '');
        }

        $results = array_filter($result, function ($recipe) {
            if (
                $recipe['total_incoming'] == 0 &&
                $recipe['total_outgoing'] == 0 &&
                $recipe['total_incoming'] == 0 &&
                $recipe['initial_stock'] == 0 &&
                $recipe['total'] == 0
            ) {
                return false;
            }

            return true;
        });

        return responder()->success(array_values($result))->respond(Response::HTTP_OK);
    }

    public function getInventoryArchiveReport(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'parent_category_id' => 'nullable|exists:recipe_parent_categories,id',
            'recipe_category_id' => 'nullable|exists:recipe_categories,id,is_deleted,0',
            'recipe_id' => 'nullable|exists:recipes,id',
            'capture_date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = InventoryArchive::with([
            'department:id,name',
            'recipe:id,name,recipe_category_id',
            'recipe.recipeCategory:id,name,category_id',
            'recipe.recipeCategory.category:id,name',
            'recipe.unit:id,name'
        ]);

        // Apply filters
        if ($request->filled('department_id') && $request->department_id !== '') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('parent_category_id') && $request->parent_category_id !== '') {
            $query->whereHas('recipe.recipeCategory.category', function ($q) use ($request) {
                $q->where('id', $request->parent_category_id);
            });
        }

        if ($request->filled('recipe_category_id') && $request->recipe_category_id !== '') {
            $query->whereHas('recipe.recipeCategory', function ($q) use ($request) {
                $q->where('id', $request->recipe_category_id);
            });
        }

        if ($request->filled('recipe_id') && $request->recipe_id !== '') {
            $query->where('recipe_id', $request->recipe_id);
        }

        if ($request->filled('capture_date') && $request->capture_date !== '') {
            $query->whereDate('captured_at', $request->capture_date);
        }
        // Paginate results
        $perPage = 15;
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $data = $query
            ->orderBy('captured_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // Transform data for frontend
        $transformedData = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'department' => $item->department ? [
                    'id' => $item->department->id,
                    'name' => $item->department->name,
                ] : null,
                'recipe' => $item->recipe ? [
                    'id' => $item->recipe->id,
                    'name' => $item->recipe->name,
                ] : null,
                'category' => $item->recipe && $item->recipe->recipeCategory ? [
                    'id' => $item->recipe->recipeCategory->id,
                    'name' => $item->recipe->recipeCategory->name,
                ] : null,
                'parent_category' => $item->recipe && $item->recipe->recipeCategory && $item->recipe->recipeCategory->category ? [
                    'id' => $item->recipe->recipeCategory->category->id,
                    'name' => $item->recipe->recipeCategory->category->name,
                ] : null,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'unit' => $item->recipe && $item->recipe->unit ? [
                    'id' => $item->recipe->unit->id,
                    'name' => $item->recipe->unit->name,
                ] : null,
                'captured_at' => $item->captured_at ? $item->captured_at : null,
                'capture_date' => $item->captured_at ? $item->captured_at : null,
            ];
        });

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];

        return responder()->success([
            'data' => $transformedData,
            'pagination' => $pagination,
        ])->respond(Response::HTTP_OK);
    }

    public function getAvailableCaptureDates()
    {
        $dates = InventoryArchive::selectRaw('DATE(captured_at) as capture_date')
            ->distinct()
            ->orderBy('capture_date', 'desc')
            ->pluck('capture_date')
            ->toArray();

        return responder()->success($dates)->respond(Response::HTTP_OK);
    }
}
