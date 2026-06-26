<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryArchive;
use App\Models\InvoiceRecipe;
use App\Models\RecipeCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CategoryInventoryReportController extends Controller
{
    /**
     * Get inventory report aggregated by recipe categories
     */
    public function getCategoryReport(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'parent_category_id' => 'nullable|exists:recipe_parent_categories,id',
            'warehouse_section' => 'nullable|string',
            'report_type' => 'nullable|in:1,2', // 1 = quantity, 2 = price
        ]);

        $data = $request->all();
        $department = Department::find($data['department_id']);
        $reportType = $data['report_type'] ?? 1;
        $isQuantityReport = $reportType == 1;

        // Get initial stock aggregated by category
        $initialStock = $this->getInitialStockByCategory($data, $isQuantityReport);

        // Adjust date range if initial stock exists
        if ($initialStock->count() > 0) {
            $data['from'] = $initialStock->first()->captured_at;
            $data['to'] = Carbon::parse($data['to'])->endOfDay();
        }

        // Get incoming invoices by category
        $totalIncoming = $this->getIncomingByCategory($data, $department, $isQuantityReport);

        // Get outgoing invoices by category
        $totalOutgoing = $this->getOutgoingByCategory($data, $department, $isQuantityReport);

        // Get returned from by category
        $returnedFrom = $this->getReturnedFromByCategory($data, $department, $isQuantityReport);

        // Get returned to by category
        $returnedTo = $this->getReturnedToByCategory($data, $department, $isQuantityReport);

        // Get transferred by category
        $totalTransferred = $this->getTransferredByCategory($data, $department, $isQuantityReport);

        // Get tainted by category
        $totalTainted = $this->getTaintedByCategory($data, $department, $isQuantityReport);

        // Build result array
        $result = $this->buildCategoryReport(
            $initialStock,
            $totalIncoming,
            $totalOutgoing,
            $returnedFrom,
            $returnedTo,
            $totalTransferred,
            $totalTainted,
            $isQuantityReport
        );

        return responder()->success(array_values($result))->respond(Response::HTTP_OK);
    }

    /**
     * Get initial stock aggregated by parent category
     */
    private function getInitialStockByCategory($data, $isQuantityReport)
    {
        $selectField = $isQuantityReport ? 'SUM(quantity) as value' : 'SUM(price) as value';

        $latestSnapshots = InventoryArchive::select([
                'recipe_id',
                DB::raw('MAX(captured_at) as captured_at'),
            ])
            ->where('captured_at', '<', $data['from'])
            ->where('department_id', $data['department_id'])
            ->groupBy('recipe_id');

        return InventoryArchive::joinSub($latestSnapshots, 'latest_inventory_archive', function ($join) {
                $join->on('inventory_archive.recipe_id', '=', 'latest_inventory_archive.recipe_id')
                    ->on('inventory_archive.captured_at', '=', 'latest_inventory_archive.captured_at');
            })
            ->join('recipes', 'recipes.id', '=', 'inventory_archive.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField),
                DB::raw('MAX(inventory_archive.captured_at) as captured_at')
            )
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->get();
    }

    /**
     * Get incoming invoices aggregated by parent category
     */
    private function getIncomingByCategory($data, $department, $isQuantityReport)
    {
        $selectField = $isQuantityReport 
            ? 'SUM(invoice_recipe.quantity) as total_incoming' 
            : 'SUM(invoice_recipe.total_price) as total_incoming';

        return InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->when($department->type == 'source', 
                fn($query) => $query->where('invoices.type', 'in_coming'))
            ->when($department->type != 'source', 
                fn($query) => $query->where('invoices.type', 'out_going')
                    ->where('invoices.to', $department->id))
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField)
            )
            ->get();
    }

    /**
     * Get outgoing invoices aggregated by parent category
     */
    private function getOutgoingByCategory($data, $department, $isQuantityReport)
    {
        $selectField = $isQuantityReport 
            ? 'SUM(invoice_recipe.quantity) as total_outgoing' 
            : 'SUM(invoice_recipe.total_price) as total_outgoing';

        return InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->where('invoices.type', 'out_going')
            ->where('invoices.from', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField)
            )
            ->get();
    }

    /**
     * Get returned from invoices aggregated by parent category
     */
    private function getReturnedFromByCategory($data, $department, $isQuantityReport)
    {
        $selectField = $isQuantityReport 
            ? 'SUM(invoice_recipe.quantity) as total_returned_from' 
            : 'SUM(invoice_recipe.total_price) as total_returned_from';

        return InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->where('invoices.type', 'returned')
            ->where('invoices.from', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField)
            )
            ->get();
    }

    /**
     * Get returned to invoices aggregated by parent category
     */
    private function getReturnedToByCategory($data, $department, $isQuantityReport)
    {
        $selectField = $isQuantityReport 
            ? 'SUM(invoice_recipe.quantity) as total_returned_to' 
            : 'SUM(invoice_recipe.total_price) as total_returned_to';

        return InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->where('invoices.type', 'returned')
            ->where('invoices.to', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField)
            )
            ->get();
    }

    /**
     * Get transferred invoices aggregated by parent category
     */
    private function getTransferredByCategory($data, $department, $isQuantityReport)
    {
        $selectField = $isQuantityReport 
            ? 'SUM(invoice_recipe.quantity) as total_transferred' 
            : 'SUM(invoice_recipe.total_price) as total_transferred';

        return InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->where('invoices.type', 'transfare')
            ->where(function ($query) use ($department) {
                $query->where('invoices.from', $department->id)
                      ->orWhere('invoices.to', $department->id);
            })
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField)
            )
            ->get();
    }

    /**
     * Get tainted invoices aggregated by parent category
     */
    private function getTaintedByCategory($data, $department, $isQuantityReport)
    {
        $selectField = $isQuantityReport 
            ? 'SUM(invoice_recipe.quantity) as total_tainted' 
            : 'SUM(invoice_recipe.total_price) as total_tainted';

        return InvoiceRecipe::join('invoices', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->where('invoices.type', 'tainted')
            ->where('invoices.from', $department->id)
            ->where('invoices.created_at', '>=', $data['from'])
            ->where('invoices.created_at', '<=', $data['to'])
            ->when(isset($data['parent_category_id']) && $data['parent_category_id'] != '', 
                fn($query) => $query->where('recipe_parent_categories.id', $data['parent_category_id']))
            ->when(isset($data['warehouse_section']) && $data['warehouse_section'] != '', 
                fn($query) => $query->where('recipe_parent_categories.warehouse_section_id', $data['warehouse_section']))
            ->groupBy('recipe_parent_categories.id', 'recipe_parent_categories.name')
            ->select(
                'recipe_parent_categories.id as parent_category_id',
                'recipe_parent_categories.name as parent_category_name',
                DB::raw($selectField)
            )
            ->get();
    }

    /**
     * Build the final category report with calculations
     */
    private function buildCategoryReport(
        $initialStock,
        $totalIncoming,
        $totalOutgoing,
        $returnedFrom,
        $returnedTo,
        $totalTransferred,
        $totalTainted,
        $isQuantityReport
    ) {
        $datasets = collect([
            collect($initialStock),
            collect($totalIncoming),
            collect($totalOutgoing),
            collect($returnedFrom),
            collect($returnedTo),
            collect($totalTransferred),
            collect($totalTainted),
        ]);

        $categories = $datasets
            ->flatMap(fn($collection) => $collection->pluck('parent_category_id'))
            ->unique()
            ->filter();

        $initialStockByCategory = collect($initialStock)->keyBy('parent_category_id');
        $incomingByCategory = collect($totalIncoming)->keyBy('parent_category_id');
        $outgoingByCategory = collect($totalOutgoing)->keyBy('parent_category_id');
        $returnedFromByCategory = collect($returnedFrom)->keyBy('parent_category_id');
        $returnedToByCategory = collect($returnedTo)->keyBy('parent_category_id');
        $transferredByCategory = collect($totalTransferred)->keyBy('parent_category_id');
        $taintedByCategory = collect($totalTainted)->keyBy('parent_category_id');

        $result = [];

        foreach ($categories as $categoryId) {
            $categoryName =
                optional($initialStockByCategory->get($categoryId))->parent_category_name
                ?? optional($incomingByCategory->get($categoryId))->parent_category_name
                ?? optional($outgoingByCategory->get($categoryId))->parent_category_name
                ?? optional($returnedFromByCategory->get($categoryId))->parent_category_name
                ?? optional($returnedToByCategory->get($categoryId))->parent_category_name
                ?? optional($transferredByCategory->get($categoryId))->parent_category_name
                ?? optional($taintedByCategory->get($categoryId))->parent_category_name
                ?? 'غير معروف';

            $initialValue = (float) optional($initialStockByCategory->get($categoryId))->value;
            $incomingValue = (float) optional($incomingByCategory->get($categoryId))->total_incoming;
            $outgoingValue = (float) optional($outgoingByCategory->get($categoryId))->total_outgoing;
            $returnedFromValue = (float) optional($returnedFromByCategory->get($categoryId))->total_returned_from;
            $returnedToValue = (float) optional($returnedToByCategory->get($categoryId))->total_returned_to;
            $transferredValue = (float) optional($transferredByCategory->get($categoryId))->total_transferred;
            $taintedValue = (float) optional($taintedByCategory->get($categoryId))->total_tainted;

            $result[$categoryId] = [
                'parent_category_id' => $categoryId,
                'parent_category_name' => $categoryName,
                'initial_stock' => number_format($initialValue, 3, '.', ''),
                'total_incoming' => number_format($incomingValue, 3, '.', ''),
                'total_outgoing' => number_format($outgoingValue, 3, '.', ''),
                'total_returned_to' => number_format($returnedToValue, 3, '.', ''),
                'total_returned_from' => number_format($returnedFromValue, 3, '.', ''),
                'total_transferred' => number_format($transferredValue, 3, '.', ''),
                'total_tainted' => number_format($taintedValue, 3, '.', ''),
                'total' => number_format(
                    $initialValue
                    + $incomingValue
                    - $outgoingValue
                    + $returnedToValue
                    - $returnedFromValue
                    - $taintedValue,
                    3,
                    '.',
                    ''
                ),
            ];
        }

        return array_filter($result, function ($category) {
            return !(
                $category['total_incoming'] == '0.000'
                && $category['total_outgoing'] == '0.000'
                && $category['initial_stock'] == '0.000'
                && $category['total'] == '0.000'
            );
        });
    }
}