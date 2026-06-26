<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\SearchInvoicesDepartmentRequest;
use App\Http\Requests\Recipe\DepartmentBalanceRequest;
use App\Http\Requests\Recipe\RecipeStatisticsReportRequest;
use App\Http\Requests\Recipe\RecipesUnderLimitRequest;
use App\Http\Requests\Recipe\SearchAllRecipeRequest;
use App\Http\Requests\Recipe\SearchRecipeRequest;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Models\DepartmentStore;
use App\Models\Invoice;
use App\Models\Recipe;
use App\Models\RecipeQuantity;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\Recipe\RecipeRepository;
use App\Repositories\RecipeCategory\RecipeCategoryRepository;
use App\Service\Balance\DepartmentBalance;
use App\Service\Reports\RecipeReports\RecipeExpireLimitService;
use App\Service\Reports\RecipeReports\RecipeStatisticsService;
use App\Service\Reports\RecipeReports\RecipesUnderLimitService;
use App\Transformers\Recipe\AbstractRecipeDepartmentTransformer;
use App\Transformers\Recipe\AbstractRecipeTransformer;
use App\Transformers\Recipe\ExpireRecipesTransformer;
use App\Transformers\Recipe\InvoicesRecipeTransformer;
use App\Transformers\Recipe\RecipeProductsTransformer;
use App\Transformers\Recipe\RecipeTransformer;
use App\Transformers\Store\StoreTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function __construct(
        private RecipeRepository $recipesRepository,
        private RecipeCategoryRepository $recipeCategooryRepository,
        private DepartmentRepository $departmentRepository,
        private RecipeExpireLimitService $recipeExpireLimitService,
        private RecipesUnderLimitService $recipesUnderLimitService,
        private RecipeStatisticsService $recipeStatisticsService,
        private InvoiceRepository $invoiceRepository
    ) {
        $this->recipesRepository = $recipesRepository;
        $this->recipeCategooryRepository = $recipeCategooryRepository;
        $this->departmentRepository = $departmentRepository;
        $this->recipeStatisticsService = $recipeStatisticsService;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function all()
    {
        $recipess = $this->recipesRepository->all('created_at', 'desc');

        return responder()->success($recipess, AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
    }

    // public function departmentShow($id){
    //     $departmentstore= DepartmentStore::where([
    //         'department_id'=> $id,
    //     ])->with('recipe')->latest()->get();
    //     return responder()->success($departmentstore, AbstractRecipeDepartmentTransformer::class)->respond(Response::HTTP_OK);
    // }

    public function allPaginated(SearchAllRecipeRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        if (count($data) > 0) {
            if (isset($data['category_parent_id']) && $data['category_parent_id']) {
                $category_parent_id = $data['category_parent_id'];
                unset($data['category_parent_id']);
            }

            // $
            $recipes = $this
                ->recipesRepository
                ->getInterceptedByAttributes2([])
                ->when(isset($data['name']) && $data['name'], fn($q) =>
                    $q->where('recipes.name', 'like', '%' . $data['name'] . '%'))
                ->when(isset($category_parent_id) && $category_parent_id, fn($q) =>
                    $q
                        ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
                        ->where('category_id', $category_parent_id))
                ->select('recipes.*')
                ->get();

            if (isset($date['from']) && isset($date['to'])) {
                $recipes = $recipes->whereBetween('created_at', [$date['from'], $date['to']]);
            }

            return responder()->success($this->recipesRepository->paginate($recipes), AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
        }
        $recipes = $this->recipesRepository->all();
        $recipes = $this->recipesRepository->paginate($recipes);

        return responder()->success($recipes, AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function index(SearchRecipeRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }
        if (count($data) > 0) {
            $categoryParentId = $data['category_parent_id'] ?? null;
            unset($data['category_parent_id']);

            $recipes = $this->recipesRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
            if (isset($date['from']) && isset($date['to'])) {
                $recipes = $recipes->whereBetween('created_at', [$date['from'], $date['to']]);
            }
            if ($categoryParentId) {
                $recipes = $recipes->filter(function ($recipe) use ($categoryParentId) {
                    return $recipe->recipeCategory && $recipe->recipeCategory->category_id == $categoryParentId;
                });
            }

            return responder()->success($this->recipesRepository->paginate($recipes, 20), AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
        }
        $recipes = $this->recipesRepository->all();
        $recipes = $this->recipesRepository->paginate($recipes, 20);

        return responder()->success($recipes, AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreRecipeRequest $request)
    {
        $recipes = $this->recipesRepository->adminCreate($request->validated());

        return responder()->success($recipes, AbstractRecipeTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show(RecipesUnderLimitRequest $request, $recipes_id = null)
    {
        $department_id = $request->department_id;
        $recipe = $this->recipesRepository->find($recipes_id);

        // if (!isset($department_id)){
        //         $department_id = '01hy3km07mf7fafqn2j6388d1t';
        // }
        $department = $this->departmentRepository->find($department_id);

        $data = RecipeTransformer::transform($recipe, $department);

        return responder()->success($data)->respond(Response::HTTP_OK);
    }

    public function update(UpdateRecipeRequest $request, $recipes_id)
    {
        $recipe = $this->recipesRepository->find($recipes_id);
        $this->recipesRepository->adminUpdate($recipe, $request->validated());

        return responder()->success(['message' => 'تم تعديل المكون بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($recipes_id)
    {
        $recipe = $this->recipesRepository->find($recipes_id);

        if ($recipe->invoices->count() > 0 ||
                $recipe->products->count() > 0 ||
                $recipe->departments->count() > 0 ||
                $recipe->requests->count() > 0) {
            return responder()->error("can't_delete", 'هذا المكون لا يمكن حذفه')->respond(Response::HTTP_BAD_REQUEST);
        }
        $this->recipesRepository->adminDelete($recipe);

        return responder()->success(['message' => 'تم حذف المكون بنجاح'])->respond(Response::HTTP_OK);
    }

    public function getRecipesByCategory($recipe_category_id)
    {
        $recipes = $this->recipesRepository->findByCategory($recipe_category_id);

        return responder()->success($recipes, AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function filterByCategory($category_id)
    {
        $recipes = $this->recipesRepository->findByCategory($category_id);

        return responder()->success($recipes, AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getRepicesUnderLimt(RecipesUnderLimitRequest $request)
    {
        $data = $request->validated();
        $recipes = $this->recipesUnderLimitService->getRecipesUnderLimit($data);
        return response()->json([
            'success' => true,
            'status' => Response::HTTP_OK,
            'data' => $recipes,
            'message' => 'recipes under limit returned succefully '
        ], Response::HTTP_OK);;

        // return responder()->success($this->recipesRepository->paginate($recipes), AbstractRecipeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getRecipesHasExpireDateBeforeDays(RecipesUnderLimitRequest $request)
    {
        $recipes = $this->recipeExpireLimitService->getRecipesHasExpireDateBeforeDays($request->department_id);

        return responder()->success($this->recipesRepository->paginate(collect($recipes)))->respond(Response::HTTP_OK);
    }

    public function showOneExpireDate($recipe_id, RecipesUnderLimitRequest $request)
    {
        $recipes = $this->recipeExpireLimitService->getRecipesHasExpireDateBeforeDays($request->department_id);
        $oneRecipe = [];
        foreach ($recipes as $recipe) {
            if ($recipe['id'] == $recipe_id) {
                $oneRecipe = $recipe;
                break;
            }
        }

        return responder()->success($oneRecipe, ExpireRecipesTransformer::class)->respond(Response::HTTP_OK);
    }

    public function totalStores()
    {
        $recipes = $this->recipesRepository->all();
        $formatedRecipes = [];
        foreach ($recipes as $recipe) {
            $formatedRecipes[] = StoreTransformer::transform($recipe);
        }

        foreach ($formatedRecipes as $index => $recipe) {
            if (count($recipe) == 0) {
                unset($formatedRecipes[$index]);
            }
        }
        $formatedRecipes = array_values($formatedRecipes);
        $recipes = $this->recipesRepository->paginate($formatedRecipes);

        return responder()->success($formatedRecipes)->respond(Response::HTTP_OK);
    }

    public function recipeInvoiceReport($recipe_id, RecipeStatisticsReportRequest $request)
    {
        $department = $this->departmentRepository->find($request->department_id);
        $recipe = $this->recipesRepository->find($recipe_id);
        $data = $this->recipeStatisticsService->recipeInvoiceReport($department, $recipe, $request->date, $request->type);
        $formatedData = [];

        foreach ($data as $item) {
            $item['total'] = $data['totals']['total'];
            $item['totalQuantity'] = $data['totals']['totalQuantity'];
            $formatedData[] = $item;
        }

        unset($formatedData[count($formatedData) - 1]);
        $formatedData = collect($formatedData);
        $formatedData = $this->recipesRepository->paginate(($formatedData));

        return responder()->success($formatedData)->respond(Response::HTTP_OK);
    }

    public function getRecipesOutGoingFromToDate(SearchInvoicesDepartmentRequest $request, $department_id)
    {
        $department = $this->departmentRepository->find($department_id);
        $recipes = $department->recipes()->get();
        $formattedRecipes = [];

        foreach ($recipes as $recipe) {
            $invoices = $this->getInvoicesForRecipe($recipe, $request);

            $totals = $this->calculateTotalsForRecipe($invoices, $department);

            $formattedRecipes[] = $this->formatRecipeWithTotals($recipe, $department, $totals);
        }

        return responder()->success($formattedRecipes)->respond(Response::HTTP_OK);
    }

    private function getInvoicesForRecipe($recipe, $request)
    {
        if ($request->date) {
            return $recipe
                ->invoices()
                ->whereBetween('invoice_date', [$request->from, $request->to])
                ->get();
        }

        return $recipe->invoices()->get();
    }

    private function calculateTotalsForRecipe($invoices, $department)
    {
        $totals = [
            'total_out_going' => 0,
            'total_returned' => 0,
            'total_out_going_price' => 0,
            'returned_total_price' => 0,
        ];

        foreach ($invoices as $invoice) {
            if ($invoice->type === 'out_going' && $invoice->to == $department->id) {
                $totals['total_out_going'] += $invoice->pivot->quantity;
                $totals['total_out_going_price'] += $invoice->pivot->price;
            } elseif ($invoice->type === 'returned' && $invoice->from == $department->id) {
                $totals['total_returned'] += $invoice->pivot->quantity;
                $totals['returned_total_price'] += $invoice->pivot->price;
            }
        }

        return $totals;
    }

    private function formatRecipeWithTotals($recipe, $department, $totals)
    {
        $formattedRecipe = RecipeTransformer::transform($recipe, $department);
        $formattedRecipe['total_out_going'] = $totals['total_out_going'];
        $formattedRecipe['total_returned'] = $totals['total_returned'];
        $formattedRecipe['total'] = $totals['total_out_going'] - $totals['total_returned'];
        $formattedRecipe['total_price'] = $totals['total_out_going_price'] - $totals['returned_total_price'];

        return $formattedRecipe;
    }

    public function getRecipesOutGoingToDepartmentFromToDate(SearchInvoicesDepartmentRequest $request, $department_id)
    {
        $data = Invoice::join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
            ->where('to', $department_id)
            ->where('type', 'out_going')
            ->select('invoice_recipe.recipe_id', DB::raw('SUM(invoice_recipe.quantity) as total_quantity'))
            ->groupBy('invoice_recipe.recipe_id')
            ->get();

        if ($request->from && $request->to) {
            $data = $data->whereBetween('invoice_date', [$request->from, $request->to]);
        }

        return responder()->success($data, InvoicesRecipeTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function getDepartmentBalanceFromToDate(DepartmentBalanceRequest $request)
    {
        $to_date = $request->toDate ? $request->toDate : Carbon::now()->format('Y-m-d');
        $data = (new DepartmentBalance)->getDepartmentBalances($request->department_id, $request->from_date, $to_date);

        return responder()->success($data)->respond(Response::HTTP_OK);
    }

    public function recipeProducts($recipe_id)
    {
        $recipe = $this->recipesRepository->find($recipe_id);

        return responder()->success($recipe, RecipeProductsTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getAllRecipeInvoices(Request $request)
    {
        $data = $request->input('data', []);
        

        $recipeInvoices = $this->recipesRepository->getAllRecipeInvoices();

        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
            $recipeInvoices = $recipeInvoices->whereBetween('invoice_date', [$date['from'], $date['to']]);
        }

        if (array_key_exists('recipe_name', $data) && !empty($data['recipe_name'])) {
            $recipeInvoices = $recipeInvoices->filter(function ($invoice) use ($data) {
                return stripos($invoice['recipe']['name'], $data['recipe_name']) !== false;
            });
        }

        $recipeInvoices = $this->recipesRepository->paginate($recipeInvoices, 50);

        // paginate here
        return responder()->success($recipeInvoices)->respond(Response::HTTP_OK);
    }

    public function deleteRecipe($recipe_id)
    {
        $recipe = $this->recipesRepository->find($recipe_id);
        if ($recipe->invoices->count() > 0) {
            foreach ($recipe->invoices as $invoice) {
                $this->invoiceRepository->adminDelete($invoice);
            }
        }

        if ($recipe->products->count() > 0) {
            foreach ($recipe->products as $product) {
                $product->delete();
            }
        }

        if ($recipe->departments->count() > 0) {
            foreach ($recipe->departments as $department) {
                $department->pivot->delete();
            }
        }

        foreach (RecipeQuantity::where('recipe_id', $recipe_id)->get() as $recipeQuantity) {
            $recipeQuantity->delete();
        }

        Storage::disk('public')->delete($recipe->image ?? '');
        $recipe->delete();

        return responder()->success(['message' => 'تم حذف المكون بنجاح'])->respond(Response::HTTP_OK);
    }

    public function changeRecipeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
        ]);
        $recipe = Recipe::find($id);
        $recipe->update([
            'status' => $request->status,
        ]);

        return responder()->success(['message' => 'تم تعديل حالة الصنف بنجاح'])->respond(Response::HTTP_OK);
    }
}
