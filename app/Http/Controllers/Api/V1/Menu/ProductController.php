<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DiscountReason;
use App\Models\SubCategory;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use App\Transformers\Product\AbstractProductDepartmentTransformer;
use App\Transformers\Product\AbstractProductTransformer;
use App\Transformers\Product\ProductTransformer;
use App\Transformers\SubCategory\AbstractSubCategoryTransformer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class ProductController extends Controller
{
    public function __construct(private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SubCategoryRepository $subCategoryRepository)
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->subCategoryRepository = $subCategoryRepository;
    }

    public function show($product_id)
    {
        $product = $this->productRepository->find($product_id);

        return responder()->success($product, ProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function filterBySubCategory($subCategory_id)
    {
        $departmentId = Auth::user('api')->department_id;
        $subcategories = Subcategory::whereHas('products', function ($query) use ($departmentId) {
            $query->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        })->find($subCategory_id);
        $products = $subcategories?->products()->whereHas('departments', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        });

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function index()
    {
        $department = Department::with('products')->findOrFail(Auth::user('api')->department_id);
        $products = $department->products;

        return responder()->success($products, AbstractProductDepartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function subcategory()
    {
        $departmentId = Auth::user('api')->department_id;
        $subcategories = Subcategory::whereHas('products', function ($query) use ($departmentId) {
            $query->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        })->get();

        return responder()->success($subcategories, AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function changeuuid()
    {
        DiscountReason::get()->each(function ($row) {
            DiscountReason::where('id', $row->id)
                ->update(['id' => Uuid::uuid4()->toString()]);
        });
    }
}
