<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecipeCategory\SearchRecipeCategoryRequest;
use App\Http\Requests\RecipeCategory\StoreRecipeCategoryRequest;
use App\Http\Requests\RecipeCategory\UpdateRecipeCategoryRequest;
use App\Repositories\Recipe\RecipeRepository;
use App\Repositories\RecipeCategory\RecipeCategoryRepository;
use App\Repositories\RecipeCategoryParent\RecipeCategoryParentRepository;
use App\Transformers\RecipeCategory\AbstractRecipeCategoryTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;
use Illuminate\Http\Response;

class RecipeCategoryController extends Controller
{
    public function __construct(private RecipeCategoryRepository $recipeCategoryRepository, private RecipeCategoryParentRepository $recipeCategoryParentRepository)
    {
        $this->recipeCategoryRepository = $recipeCategoryRepository;
        $this->recipeCategoryParentRepository = $recipeCategoryParentRepository;
    }

    public function index(SearchRecipeCategoryRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        if (count($data) > 0) {
            $categories = $this->recipeCategoryRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
            if (isset($date['from']) && isset($date['to'])) {
                $categories = $categories->whereBetween('created_at', [$date['from'], $date['to']]);
            }

            if (isset($data['name'])) {

                $matchedRecipes = resolve(RecipeRepository::class)->getInterceptedByAttributes(['name' => $data['name']], 'created_at', 'desc');
                $matchingRecipeCategories = $matchedRecipes->pluck('recipeCategory')->unique('id');
                $categories = $categories->merge($matchingRecipeCategories)->unique('name');
            }
            if (isset($data['category_id'])) {
                $categories = $categories->where('category_id', $data['category_id']);
            }

            return responder()->success($this->recipeCategoryRepository->paginate($categories), AbstractRecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
        }

        $categories = $this->recipeCategoryRepository->allPaginated('created_at', 'desc');
        if (isset($date['from']) && isset($date['to'])) {
            $categories = $categories->whereBetween('created_at', [$date['from'], $date['to']]);
        }

        return responder()->success($categories, AbstractRecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function department(SearchRecipeCategoryRequest $request)
    {
        // $data = $request->validated();
        // if(isset($data['date']) && $data['date']){
        //     $date = $data['date'];
        //     unset($data['date']);
        // }
        // if(count($data) > 0){
        //     $categories = $this->recipeCategoryRepository->getInterceptedByAttributes($data,'created_at', 'desc');
        //     if(!empty($date) && $date){
        //         $categories = $categories->whereBetween('created_at', [$date['from'], $date['to']]);
        //     }
        //     return responder()->success($this->recipeCategoryRepository->paginate($categories), AbstractRecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
        // }

        $categories = $this->recipeCategoryRepository->allPaginated('created_at', 'desc');

        return responder()->success($categories, AbstractRecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreRecipeCategoryRequest $request)
    {
        $recipeCategory = $this->recipeCategoryRepository->adminCreate($request->validated());

        return responder()->success($recipeCategory, AbstractRecipeCategoryTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($recipe_category_id)
    {
        $recipeCategory = $this->recipeCategoryRepository->find($recipe_category_id);

        return responder()->success($recipeCategory, RecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateRecipeCategoryRequest $request, $recipe_category_id)
    {
        $recipeCategory = $this->recipeCategoryRepository->find($recipe_category_id);
        $this->recipeCategoryRepository->adminUpdate($recipeCategory, $request->validated());

        return responder()->success(['message' => 'تم تعديل  التصنيف الرئيسي للمكونات بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($recipe_category_id)
    {
        $recipeCategory = $this->recipeCategoryRepository->find($recipe_category_id);
        if ($recipeCategory->recipes->count() > 0) {
            return responder()->error("can't_delete", 'لا يمكن حذف  التصنيف الرئيسي للمكونات لوجود تصنيفات أو المنتجات فيها')->respond(Response::HTTP_BAD_REQUEST);
        }

        $this->recipeCategoryRepository->adminDelete($recipeCategory);

        return responder()->success(['message' => 'تم حذف  التصنيف الرئيسي للمكونات بنجاح'])->respond(Response::HTTP_OK);
    }

    public function filterByParent($category_id)
    {
        $parentCategory = $this->recipeCategoryParentRepository->find($category_id);
        $recipeCategories = $parentCategory->subCategories;

        return responder()->success($recipeCategories, RecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function all()
    {
        $recipeCategory = $this->recipeCategoryRepository->all();

        return responder()->success($recipeCategory, AbstractRecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getByCategory($category_id)
    {
        $recipeCategories = $this->recipeCategoryRepository->findByCategory($category_id);

        if ($recipeCategories->isEmpty()) {
            return responder()->error('not_found', 'No categories found for this category ID.')->respond(Response::HTTP_NOT_FOUND);
        }

        // Return the categories in the response
        return responder()->success($recipeCategories, RecipeCategoryTransformer::class)->respond(Response::HTTP_OK);
    }
}
