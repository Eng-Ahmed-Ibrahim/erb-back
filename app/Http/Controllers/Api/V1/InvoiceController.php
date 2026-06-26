<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\MoveInvoiceRequest;
use App\Http\Requests\Invoices\SearchInvoicesDepartmentRequest;
use App\Http\Requests\Invoices\SearchInvoicesRequest;
use App\Http\Requests\Invoices\StoreInvoicesRequest;
use App\Http\Requests\Invoices\UpdateInvoicePriceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceQuantityRequest;
use App\Models\Invoice;
use App\Models\ModelHasModel;
use App\Models\RecipeParentCategory;
use App\Models\Role;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Invoice\InvoiceRepository;
use App\Service\Factory\InvoiceFactory;
use App\Transformers\Invoices\AbstractInvoiceTransformer;
use App\Transformers\Invoices\InCommingInvoiceTransformer;
use App\Transformers\Invoices\InvoiceTransformer;
use App\Transformers\Recipe\InvoicesRecipeTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Fractal\Resource\Collection;
use League\Fractal\Manager;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private DepartmentRepository $departmentRepository
    ) {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function index(SearchInvoicesRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }
        if (count($data) > 0) {
            $invoices = $this->invoiceRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
            if (isset($date['from']) && isset($date['to'])) {
                $invoices = $invoices->whereBetween('invoice_date', [$date['from'], $date['to']]);
            }

            return responder()->success($this->invoiceRepository->paginate($invoices), AbstractInvoiceTransformer::class)->respond(Response::HTTP_OK);
        }
        $invoices = $this->invoiceRepository->all();
        $invoices = $this->invoiceRepository->paginate($invoices);

        return responder()->success($invoices, AbstractInvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreInvoicesRequest $request)
    {
        $data = $request->validated();
        $invoice = InvoiceFactory::invoiceBasedOnType($data['type'])->createInvoice($data);
        if (!$invoice['status']) {
            return responder()->error('error', $invoice['message'])->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success($invoice, InvoiceTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function moveInvoiceToDepartment(MoveInvoiceRequest $request)
    {
        $data = $request->validated();

        if (isset($data) && $data['code']) {
            $invoice = Invoice::where('code', $data['code'])->where('status', 'approved')->where('type', 'in_coming');
            if (!$invoice->exists()) {
                return responder()->error('error', 'لا يمكن صرف الفاتورة قبل مراجعتها')->respond(Response::HTTP_BAD_REQUEST);
            }
        }

        $invoice = $this->invoiceRepository->moveInvoiceToDepartment($data);

        if (empty($invoice)) {
            return responder()->error('error', 'هذه الفاتورة تم صرفها من قبل')->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success($invoice, InvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show($invoice_id)
    {
        $invoice = $this->invoiceRepository->find($invoice_id);

        return responder()->success($invoice, InvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateInvoicePriceRequest $request, $invoice_id)
    {
        $data = $request->validated();
        $invoice = $this->invoiceRepository->find($invoice_id);
        if ($invoice->status == 'approved') {
            return responder()->error("can't_update", 'لا يمكن تعديل هذه الفاتورة');
        }

        $updateInvoice = InvoiceFactory::invoiceBasedOnType($invoice['type'])->updateInvoicePrices($invoice, $data);

        if (!$updateInvoice['status']) {
            return responder()->error('error', $updateInvoice['message'])->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success(['message' => 'تم تعديل الفاتورة بنجاح'])->respond(Response::HTTP_OK);
    }

    public function updateQuantity(UpdateInvoiceQuantityRequest $request, $invoice_id)
    {
        $data = $request->validated();
        $invoice = $this->invoiceRepository->find($invoice_id);
        if ($invoice->status == 'approved') {
            return responder()->error("can't_update", 'لا يمكن تعديل هذه الفاتورة');
        }

        $result = InvoiceFactory::invoiceBasedOnType($invoice['type'])->updateInvoiceQuantity($invoice, $data);

        if (!$result['status']) {
            return responder()->error('error', $result['message'])->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success(['message' => 'تم تعديل الفاتورة بنجاح'])->respond(Response::HTTP_OK);
    }

    public function updateInvoiceData(Request $request, $invoice_id)
    {
        $request->validate([
            'data.invoice_date' => 'required|date',
            'data.code' => 'required',
        ]);

        $data = $request->data;
        $invoice = $this->invoiceRepository->find($invoice_id);

        $status = $this->invoiceRepository->adminUpdate($invoice, $data);

        if (!$status) {
            return responder()->error('error', 'حدث خطأ أثناء التعديل')->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success(['message' => 'تم تعديل الفاتورة بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($invoice_id)
    {
        $invoice = $this->invoiceRepository->find($invoice_id);
        if ($invoice->status == 'approved') {
            return responder()->error("can't_delete", 'لا يمكن حذف فاتورة تمت الموافقة عليها');
        }
        $this->invoiceRepository->adminDelete($invoice);

        return responder()->success(['message' => 'تم حذف الفاتورة بنجاح'])->respond(Response::HTTP_OK);
    }

    public function filterByFromDepartment(SearchInvoicesDepartmentRequest $request, $department_id)
    {
        $department = $this->departmentRepository->find($department_id);
        $invoices = $department->fromInvoices()->orderBy('created_at', 'desc')->get();
        if ($request->from && $request->to) {
            $invoices = $invoices->whereBetween('invoice_date', [$request->from, $request->to]);
        }

        return responder()->success($this->invoiceRepository->paginate($invoices), AbstractInvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function filterByToDepartment(SearchInvoicesDepartmentRequest $request, $department_id)
    {
        $department = $this->departmentRepository->find($department_id);
        $invoices = $department->toInvoices()->orderBy('created_at', 'desc')->get();
        if ($request->from && $request->to) {
            $invoices = $invoices->whereBetween('invoice_date', [$request->from, $request->to]);
        }

        return responder()->success($this->invoiceRepository->paginate($invoices), AbstractInvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function changeStatus($invoice_id, $status)
    {
        $invoice = $this->invoiceRepository->find($invoice_id);
        $invoice->status = $status;

        $invoice->save();

        return responder()->success(['message' => 'تم مراجعة الفاتورة بنجاح'])->respond(Response::HTTP_OK);
    }

    /**
     * Add a recipe to an existing invoice
     */
    public function addRecipe(Request $request, $id)
    {
        $request->validate([
            'recipe_id' => 'required|string|exists:recipes,id',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0.01',
            'expire_date' => 'required|date|after:today',
        ]);

        $invoice = $this->invoiceRepository->find($id);

        // Check if invoice exists
        if (!$invoice) {
            return responder()->error('error', 'الفاتورة غير موجودة')->respond(Response::HTTP_NOT_FOUND);
        }

        // Check if invoice is not approved
        if ($invoice->status === 'approved') {
            return responder()->error('error', 'لا يمكن إضافة مكونات إلى فاتورة تمت الموافقة عليها')->respond(Response::HTTP_BAD_REQUEST);
        }

        // Check if recipe already exists in invoice
        $existingRecipe = $invoice->recipes()->where('recipe_id', $request->recipe_id)->first();
        if ($existingRecipe) {
            return responder()->error('error', 'المكون موجود بالفعل في الفاتورة')->respond(Response::HTTP_BAD_REQUEST);
        }

        try {
            // Add recipe to invoice

            $invoice->recipes()->attach($request->recipe_id, [
                'price' => 0,
                'quantity' => 0,
                'expire_date' => $request->expire_date,
                'total_price' => 0,
                'status' => 'active',
            ]);
            // Log::dddddddddddddddddd();

            $result = InvoiceFactory::invoiceBasedOnType($invoice['type'])->updateInvoiceQuantity($invoice, ['recipes' => [['recipe_id' => $request->recipe_id, 'price' => $request->price, 'quantity' => $request->quantity, 'expire_date' => $request->expire_date]]]);

            // Update invoice total
            $totalPrice = $invoice->recipes()->sum('total_price');
            $invoice->update([
                'invoice_price' => $totalPrice,
                'total_price' => $totalPrice + $invoice->tax - $invoice->discount,
            ]);

            return responder()->success(['message' => 'تم إضافة المكون بنجاح'])->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::info('err', [$e]);
            return responder()->error('error', 'حدث خطأ أثناء إضافة المكون')->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove a recipe from an existing invoice
     */
    public function removeRecipe($id, $recipe_id)
    {
        $invoice = $this->invoiceRepository->find($id);

        // Check if invoice exists
        if (!$invoice) {
            return responder()->error('error', 'الفاتورة غير موجودة')->respond(Response::HTTP_NOT_FOUND);
        }

        // Check if invoice is not approved
        if ($invoice->status === 'approved') {
            return responder()->error('error', 'لا يمكن حذف مكونات من فاتورة تمت الموافقة عليها')->respond(Response::HTTP_BAD_REQUEST);
        }

        // Check if recipe exists in invoice
        $existingRecipe = $invoice->recipes()->where('recipe_id', $recipe_id)->first();

        if (!$existingRecipe) {
            return responder()->error('error', 'المكون غير موجود في الفاتورة')->respond(Response::HTTP_NOT_FOUND);
        }

        try {
            if ($existingRecipe?->pivot?->quantity != 0) {
                $result = InvoiceFactory::invoiceBasedOnType($invoice['type'])->updateInvoiceQuantity($invoice, ['recipes' => [['recipe_id' => $recipe_id, 'price' => $existingRecipe?->pivot?->price, 'quantity' => 0, 'expire_date' => $existingRecipe?->pivot?->expire_date]]]);

                if (!$result['status']) {
                    return responder()->error('error', $result['message'])->respond(Response::HTTP_BAD_REQUEST);
                }
            }

            $invoice->recipes()->detach($recipe_id);

            if (!$invoice->recipes()->count() && $invoice->recipes()->sum('total_price') == 0) {
                $invoice->delete();
            }
            return responder()->success(['message' => 'تم حذف المكون بنجاح'])->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::info('error', [$e]);

            return responder()->error('error', 'حدث خطأ أثناء حذف المكون')->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getInvoicesBasedOnType(SearchInvoicesRequest $request, $type)
    {
        $data = $request->validated();
        $data['type'] = $type;

        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        if (isset($data['department_id']) && $data['department_id']) {
            $data['to'] = $data['department_id'];
            unset($data['department_id']);
        }

        $user = auth()->user();
        if (
            !isset($data['created_by']) &&
            $user->roles()->first()->id != Role::ADMIN_ROLE_ID &&
            $user->roles()->first()->id != Role::ACCOUNTS_ROLE_ID &&
            $user->roles()->first()->id != Role::COST_CONTROLLER_ROLE_ID &&
            !($user->roles()->first()->id == Role::FOOD_AND_BEVERAGE_MANAGER_ROLE_ID && ($type == 'out_going' || $type == 'returned'))
        ) {
            $data['created_by'] = $user->id;
        }

        if (isset($data['status'])) {
            $data['invoices.status'] = $data['status'];
            unset($data['status']);
        }

        $invoicesQuery = Invoice::where('type', $type);
        $invoicesQuery = $this->invoiceRepository->getInterceptedByAttributes2($data);

        $query = ModelHasModel::where('target_model_id', $user->id)
            ->where('operation', 'reviewed by');
        if ($query->exists()) {
            $invoicesQuery->whereIN('created_by', $query->pluck('source_model_id'));
        }

        $invoices = $invoicesQuery
            ->select('invoices.*')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->get();

        if (!empty($date)) {
            $invoices = $invoices->whereBetween('created_at', [$date['from'], Carbon::parse($date['to'])->endofDay()]);
        }

        $totalInvoicePrice = $invoices->sum('invoice_price');
        $paginatedInvoices = $this->invoiceRepository->paginate($invoices);

        $fractal = new Manager;

        $resource = new Collection($paginatedInvoices->items(), new InvoiceTransformer);
        $transformedInvoices = $fractal->createData($resource)->toArray();

        return responder()->success([
            'data' => $transformedInvoices['data'],  // Transformed invoice data
            'pagination' => [
                'current_page' => $paginatedInvoices->currentPage(),
                'last_page' => $paginatedInvoices->lastPage(),
                'per_page' => $paginatedInvoices->perPage(),
                'total' => $paginatedInvoices->total(),
            ],
            'total' => $totalInvoicePrice,
        ])->respond(Response::HTTP_OK);
    }

    public function getRecipesOutGoingFromToDate(SearchInvoicesDepartmentRequest $request, $department_id)
    {
        $recipes = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->when($request->name, fn($query) => $query->whereRaw('recipes.name LIKE ?', ['%' . $request->name . '%']))
            ->when($request->warehouse_section_id, fn($query) => $query->whereIN('recipe_categories.category_id', RecipeParentCategory::where('warehouse_section_id', $request->warehouse_section_id)->pluck('id')->toArray()))
            ->when($request->from, fn($query) => $query
                ->whereDate('invoices.created_at', '>=', $request->from))
            ->when($request->to, fn($query) => $query
                ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
            ->where(function ($q) use ($department_id) {
                $q
                    ->where('invoices.to', $department_id)
                    ->orWhere('invoices.from', $department_id);
            })
            ->select(
                'invoice_recipe.recipe_id',
                'recipes.name',
                'recipes.minimum_limt',
            )
            ->groupBy('invoice_recipe.recipe_id')
            ->get();

        foreach ($recipes as &$recipe) {
            $outGoings = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from))
                ->when($request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
                ->where('invoice_recipe.recipe_id', $recipe['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->where('invoices.to', $department_id)
                ->where('invoices.type', 'out_going');
            $outGoingQunatity = $outGoings->sum('invoice_recipe.quantity');
            $outGoingPrice = $outGoings->sum('invoice_recipe.total_price');
            $recipe['out_going'] = number_format($outGoingQunatity, 3, '.', '');

            $returnedFrom = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from))
                ->when($request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
                ->where('invoice_recipe.recipe_id', $recipe['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->where('invoices.from', $department_id)
                ->where('invoices.type', 'returned');
            $returnedFromQuantity = $returnedFrom->sum('invoice_recipe.quantity');
            $returnedFromPrice = $returnedFrom->sum('invoice_recipe.total_price');
            $recipe['returned_from'] = $returnedFromQuantity;

            $returnedTo = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from))
                ->when($request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
                ->where('invoice_recipe.recipe_id', $recipe['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->where('invoices.to', $department_id)
                ->where('invoices.type', 'returned');
            $returnedToQuantity = $returnedTo->sum('invoice_recipe.quantity');
            $returnedToPrice = $returnedTo->sum('invoice_recipe.total_price');
            $recipe['returned_to'] = $returnedToQuantity;

            $tainted = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from))
                ->when($request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
                ->where('invoice_recipe.recipe_id', $recipe['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->where('invoices.from', $department_id)
                ->where('invoices.type', 'tainted');
            $taintedQunatity = $tainted->sum('invoice_recipe.quantity');
            $taintedPrice = $tainted->sum('invoice_recipe.total_price');
            $recipe['tainted'] = $taintedQunatity;

            $transfareFrom = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from))
                ->when($request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
                ->where('invoice_recipe.recipe_id', $recipe['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->where('invoices.from', $department_id)
                ->where('invoices.type', 'transfare');

            $transfareTo = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from))
                ->when($request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '<=', Carbon::parse($request->to)->endofDay()))
                ->where('invoice_recipe.recipe_id', $recipe['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->where('invoices.to', $department_id)
                ->where('invoices.type', 'transfare');
            $transfareQuantity = $transfareTo->sum('invoice_recipe.quantity') - $transfareFrom->sum('invoice_recipe.quantity');
            $transfarePrice = $transfareTo->sum('invoice_recipe.total_price') - $transfareFrom->sum('invoice_recipe.total_price');
            $recipe['transfare'] = $transfareQuantity;

            // $item['total_quantity'] = number_format($item['total_quantity'] - $returnedFromQuantity + $returnedToQuantity - $taintedQunatity, 3, '.', '');
            // $item['total_price'] = number_format($item['total_price'] - $returnedFromPrice + $returnedToPrice - $taintedPrice, 3, '.', '');

            $recipe['total_quantity'] = number_format($outGoingQunatity - $returnedFromQuantity + $returnedToQuantity + $transfareQuantity, 3, '.', '');
            $recipe['total_price'] = number_format($outGoingPrice - $returnedFromPrice + $returnedToPrice + $transfarePrice, 3, '.', '');
        }

        $total_price = $recipes->sum('total_price');

        return responder()->success(['data' => $recipes, 'total_price' => $total_price])->respond(Response::HTTP_OK);
    }

    public function getRecipesOutGoingFromDepartmentFromToDate(SearchInvoicesDepartmentRequest $request, $department_id)
    {
        $query = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('invoices.from', $department_id)
            // ->where('invoices.type', 'out_going')
            // ->where('invoices.status', 'approved')
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->join('recipe_parent_categories', 'recipe_parent_categories.id', '=', 'recipe_categories.category_id')
            ->when($request->category_id, fn($query) => $query
                ->where('recipe_categories.category_id', $request->category_id))
            ->when($request->name, fn($query) => $query
                ->whereRaw('recipes.name LIKE ?', ['%' . $request->name . '%']))
            ->select(
                'recipe_parent_categories.id as category_id',
                'invoice_recipe.recipe_id',
                DB::raw('AVG(invoice_recipe.price) as price'),
                'recipes.name',
                'recipes.image',
                'recipes.minimum_limt',
                DB::raw('SUM(invoice_recipe.quantity) as total_quantity')
            )
            ->groupBy('invoice_recipe.recipe_id');

        if ($request->from && $request->to) {
            $query
                ->whereDate('invoices.created_at', '>=', $request->from)
                ->whereDate('invoices.created_at', '<=', $request->to);
        }

        $data = $query->get();
        foreach ($data as &$item) {
            $item['out_going'] = $item['total_quantity'];

            $returnedFrom = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->when($request->from && $request->to, fn($query) => $query
                    ->whereDate('invoices.created_at', '>=', $request->from)
                    ->whereDate('invoices.created_at', '<=', $request->to))
                ->where('invoices.from', $department_id)
                ->where('invoices.type', 'returned')
                ->where('invoice_recipe.recipe_id', $item['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->sum('invoice_recipe.quantity');

            $returnedTo = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->where('invoices.to', $department_id)
                ->where('invoices.type', 'returned')
                ->where('invoice_recipe.recipe_id', $item['recipe_id'])
                ->groupBy('invoice_recipe.recipe_id')
                ->sum('invoice_recipe.quantity');

            $item['returnedFrom'] = $returnedFrom;
            $item['returnedTo'] = $returnedTo;
            $item['total_quantity'] = $item['total_quantity'] - $returnedFrom + $returnedTo;

            // $item['image'] = (string) config('app.url') . $item['image'];
        }
        $total_price = $data->sum('total_price');

        return responder()->success(['data' => $data, 'total_price' => $total_price])->respond(Response::HTTP_OK);
    }

    public function getInCommingInvoices(SearchInvoicesDepartmentRequest $request)
    {
        $data = $request->validated();

        $invoices = $this->invoiceRepository->where('supplier_id', null, '!=')
            ->when(isset($data['date']) && $data['date'] && isset($data['date']['from']), fn($query) => $query->where('created_at', '>=', $data['date']['from']))
            ->when(isset($data['date']) && $data['date'] && isset($data['date']['to']), fn($query) => $query->where('created_at', '<=', $data['date']['to']))
            ->where('is_paid', false)->get();

        return responder()->success($invoices, InCommingInvoiceTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getSuppliersRecipesFromToDate(SearchInvoicesDepartmentRequest $request, $supplier_id)
    {
        $data = $request->validated();
        $query = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('invoices.supplier_id', $supplier_id)
            ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
            ->join('suppliers', 'suppliers.id', '=', 'invoices.supplier_id')
            ->select(
                'invoice_recipe.recipe_id',
                'recipes.name',
                'recipes.image',
                'recipes.minimum_limt',
                DB::raw('SUM(invoice_recipe.quantity) as total_quantity'),
                DB::raw('SUM(invoice_recipe.total_price) as price')
            )
            ->groupBy('invoice_recipe.recipe_id')
            ->when(isset($data['date']) && $data['date'] && isset($data['date']['from']), fn($query) => $query->where('invoices.created_at', '>=', $data['date']['from']))
            ->when(isset($data['date']) && $data['date'] && isset($data['date']['to']), fn($query) => $query->where('invoices.created_at', '<=', Carbon::parse($data['date']['to'])->endofDay()));

        $data = $query->get();
        $data = $this->invoiceRepository->paginate($data);

        return responder()->success($data, InvoicesRecipeTransformer::class)->respond(Response::HTTP_OK);

        $data = $query->get();

        $formatedRecipes = [];
        foreach ($data as $item) {
            $formatedRecipes[] = InvoicesRecipeTransformer::transform($item);
        }
        $formatedRecipes['total'] = $data->sum('total_price');

        return responder()->success($formatedRecipes)->respond(Response::HTTP_OK);
    }
}
