<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\SearchCategoryRequest;
use App\Http\Requests\RecipeCategoryParent\StoreRecipeCategoryParentRequest;
use App\Http\Requests\RecipeCategoryParent\UpdateRecipeCategoryParentRequest;
use App\Models\ModelHasParentCategory;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeParentCategory;
use App\Repositories\RecipeCategoryParent\RecipeCategoryParentRepository;
use App\Transformers\RecipeCategoryParent\AbstractRecipeCategoryParentTransformer;
use App\Transformers\RecipeCategoryParent\AbstractRecipeCategorySearchTransformer;
use App\Transformers\RecipeCategoryParent\RecipeCategoryParentTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RecipeCategoryParentController extends Controller
{
    public function __construct(
        private RecipeCategoryParentRepository $recipeCategoryParentRepository
    ) {
        $this->recipeCategoryParentRepository = $recipeCategoryParentRepository;
    }

    public function index(SearchCategoryRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        $whitelistedCategories = ModelHasParentCategory::where('model_id', $user->id)
            ->where('model', 'AppModelsUser')
            ->pluck('category_id')
            ->toArray();

        $categoriesQuery = RecipeParentCategory::when(isset($data['date']) &&
            $data['date'] &&
            isset($data['date']['from']) &&
            isset($data['date']['from']), fn ($query) => $query
                ->whereBetween('created_at', [$data['date']['from'], $data['date']['to']]));

        foreach ($data as $key => $value) {
            $categoriesQuery->where($key, $value);
        }

        if (! empty($whitelistedCategories)) {
            $categoriesQuery->whereIn('id', $whitelistedCategories);
        }

        return responder()->success($categoriesQuery->get(), AbstractRecipeCategoryParentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show($recipe_category_id)
    {
        $recipeCategory = $this->recipeCategoryParentRepository->find($recipe_category_id);

        return responder()->success($recipeCategory, RecipeCategoryParentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function all()
    {
        $recipeCategory = $this->recipeCategoryParentRepository->all();

        return responder()->success($recipeCategory, RecipeCategoryParentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreRecipeCategoryParentRequest $request)
    {
        $recipeCategory = $this->recipeCategoryParentRepository->adminCreate($request->validated());

        return responder()->success($recipeCategory, AbstractRecipeCategoryParentTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateRecipeCategoryParentRequest $request, $recipe_category_id)
    {
        $recipeCategory = $this->recipeCategoryParentRepository->find($recipe_category_id);
        $this->recipeCategoryParentRepository->adminUpdate($recipeCategory, $request->validated());

        return responder()->success(['message' => 'تم تعديل  التصنيف الرئيسي للمكونات بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($recipe_category_id)
    {
        $recipeCategory = $this->recipeCategoryParentRepository->find($recipe_category_id);
        if ($recipeCategory->subCategories->count() > 0) {
            return responder()->error("can't_delete", 'لا يمكن حذف  التصنيف الرئيسي للمكونات لوجود تصنيفات فرعية اخري فيها')->respond(Response::HTTP_BAD_REQUEST);
        }
        $this->recipeCategoryParentRepository->adminDelete($recipeCategory);

        // $recipeCategory->delete();
        return responder()->success(['message' => 'تم حذف  التصنيف الرئيسي للمكونات بنجاح'])->respond(Response::HTTP_OK);
    }

    public function searchItems(Request $request)
    {
        $search = $request->query('search', '');

        $user = auth()->user();
        $whitelistedCategories = ModelHasParentCategory::where('model_id', $user->id)
            ->where('model', 'AppModelsUser')
            ->pluck('category_id')
            ->toArray();

        $categories = $this->recipeCategoryParentRepository->get()->toArray();
        $categories = array_filter($categories, function ($category) use ($whitelistedCategories) {
            if (in_array($category['id'], $whitelistedCategories)) {
                return true;
            }
        });

        $recipeCategories = RecipeCategory::all()->toArray();
        $recipes = Recipe::all()->toArray();
        if ($search === '') {
            return responder()->success($categories, AbstractRecipeCategoryParentTransformer::class)->respond(Response::HTTP_OK);
        }

        $filteredCategories = array_filter($categories, function ($category) use ($search, $recipeCategories, $recipes) {
            if (str_contains($category['name'], $search)) {
                return true;
            }
            foreach ($recipeCategories as $recipeCategory) {
                if ($recipeCategory['category_id'] == $category['id']) {
                    if (str_contains($recipeCategory['name'], $search)) {
                        return true;
                    }
                    foreach ($recipes as $recipe) {
                        if ($recipe['recipe_category_id'] == $recipeCategory['id'] && str_contains($recipe['name'], $search)) {
                            return true;
                        }
                    }
                }
            }

            return false;
        });

        return responder()->success(array_values($filteredCategories), AbstractRecipeCategorySearchTransformer::class)->respond(Response::HTTP_OK);
    }
}
