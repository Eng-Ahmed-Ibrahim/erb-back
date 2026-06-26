<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubCategory\SearchSubCategoryRequest;
use App\Http\Requests\SubCategory\StoreSubCategoryRequest;
use App\Http\Requests\SubCategory\UpdateSubCategoryRequest;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use App\Transformers\SubCategory\AbstractSubCategoryTransformer;
use App\Transformers\SubCategory\SubCategoryTransformer;
use Illuminate\Http\Response;

class SubCategoryController extends Controller
{
    public function __construct(private SubCategoryRepository $subCategoryRepository,
        private CategoryRepository $categoryRepository)
    {
        $this->subCategoryRepository = $subCategoryRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function index(SearchSubCategoryRequest $request)
    {
        if (count($request->validated()) > 0) {
            $categories = $this->subCategoryRepository->getInterceptedByAttributes($request->validated(), 'created_at', 'desc');

            return responder()->success($this->subCategoryRepository->paginate($categories), AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
        }
        $subCategories = $this->subCategoryRepository->allPaginated('created_at', 'asc');

        return responder()->success($subCategories, AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function index2(SearchSubCategoryRequest $request)
    {
        if (count($request->validated()) > 0) {
            $categories = $this->subCategoryRepository->filter($request->validated(), 'created_at', 'desc')->get();

            return responder()->success($this->subCategoryRepository->paginate($categories), AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
        }
        $subCategories = $this->subCategoryRepository->get();

        return responder()->success($subCategories, AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreSubCategoryRequest $request)
    {
        $subCategory = $this->subCategoryRepository->adminCreate($request->validated());

        return responder()->success($subCategory, AbstractSubCategoryTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($subCategory_id)
    {
        $subCategory = $this->subCategoryRepository->find($subCategory_id);

        return responder()->success($subCategory, SubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateSubCategoryRequest $request, $subCategory_id)
    {
        $subCategory = $this->subCategoryRepository->find($subCategory_id);
        $this->subCategoryRepository->adminUpdate($subCategory, $request->validated());

        return responder()->success(['message' => 'تم تعديل التصنيف الفرعي بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($subCategory_id)
    {

        $subCategory = $this->subCategoryRepository->find($subCategory_id);
        if ($subCategory->products->count() > 0) {
            return responder()->error("can't_delete", 'لا يمكن حذف التصنيف الفرعي لوجود المنتجات فيها')->respond(Response::HTTP_BAD_REQUEST);
        }
        $this->subCategoryRepository->adminDelete($subCategory);

        return responder()->success(['message' => 'تم حذف التصنيف الفرعي بنجاح'])->respond(Response::HTTP_OK);
    }

    public function filterByCategory($category_id)
    {
        $category = $this->categoryRepository->find($category_id);
        $subCategories = $category->subCategories()->orderBy('created_at', 'desc');

        // ->paginate(15);
        return responder()->success($subCategories, SubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function all()
    {
        $subCategories = $this->subCategoryRepository->all();

        return responder()->success($subCategories, AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }
}
