<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\SearchCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\ModelHasCategory;
use App\Repositories\Category\CategoryRepository;
use App\Transformers\Category\AbstractCategoryTransformer;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    public function index(SearchCategoryRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }

        if (count($data) > 0) {
            $categories = $this->categoryRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
            if (isset($date['from']) && isset($date['to'])) {
                $categories = $categories->whereBetween('created_at', [$date['from'], $date['to']]);
            }

            return responder()->success($this->categoryRepository->paginate($categories), AbstractCategoryTransformer::class)->respond(Response::HTTP_OK);
        }

        $userCategories = ModelHasCategory::where('model_id', auth()->user()->id)->pluck('category_id')->toArray();

        $categories = $this
            ->categoryRepository
            ->getInterceptedByAttributes2($data)
            ->when(! empty($userCategories), fn ($query) => $query
                ->whereIn('id', $userCategories));

        return responder()->success($categories, AbstractCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryRepository->adminCreate($request->validated());

        return responder()->success($category, AbstractCategoryTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($category_id)
    {
        $category = $this->categoryRepository->find($category_id);

        return responder()->success($category, AbstractCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateCategoryRequest $request, $category_id)
    {
        $category = $this->categoryRepository->find($category_id);

        $this->categoryRepository->adminUpdate($category, $request->validated());

        return responder()->success(['message' => 'تم تعديل التصنيف الرئيسي بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($category_id)
    {
        $category = $this->categoryRepository->find($category_id);
        if ($category->subCategories->count() > 0) {
            return responder()->error("can't_delete", 'لا يمكن حذف التصنيف الرئيسي بسبب وجود عناصر تحت هذا التصنيف');
        }
        $this->categoryRepository->adminDelete($category);

        return responder()->success(['message' => 'تم حذف التصنيف بنجاح'])->respond(Response::HTTP_OK);
    }

    public function all()
    {
        $categories = $this->categoryRepository->all();

        return responder()->success($categories, AbstractCategoryTransformer::class)->respond(Response::HTTP_OK);
    }
}
