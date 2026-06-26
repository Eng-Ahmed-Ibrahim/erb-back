<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\AddRecipeToProductRequest;
use App\Http\Requests\Product\AddToDepartmentRequest;
use App\Http\Requests\Product\DepartmentRequest;
use App\Http\Requests\Product\EditProductDepartmentRequest;
use App\Http\Requests\Product\SearchProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\SubcategoryRequest;
use App\Http\Requests\Product\UpdateProductPriceRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Department;
use App\Models\DepartmentProduct;
use App\Models\Product;
use App\Models\Role;
use App\Models\SubCategory;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use App\Transformers\Product\AbstractProductTransformer;
use App\Transformers\Product\CalculateProductCostPrice;
use App\Transformers\Product\ProductTransformer;
use App\Transformers\SubCategory\AbstractSubCategoryTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SubCategoryRepository $subCategoryRepository
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->subCategoryRepository = $subCategoryRepository;
    }

    public function index(SearchProductRequest $request)
    {
        $data = $request->validated();
        if (count($data) > 0) {
            $categories = $this->productRepository->getInterceptedByAttributes($data, 'created_at', 'desc');

            return responder()->success($this->productRepository->paginate($categories), AbstractProductTransformer::class)->respond(Response::HTTP_OK);
        }
        $products = $this->productRepository->allPaginated('created_at', 'desc');

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function new(SearchProductRequest $request)
    {
        $data = $request->validated();
        if (count($data) > 0) {
            $categories = $this->productRepository->getInterceptedByAttributes($data, 'created_at', 'desc');

            return responder()->success($this->productRepository->paginate($categories), AbstractProductTransformer::class)->respond(Response::HTTP_OK);
        }
        $products = $this->productRepository->allPaginated('created_at', 'desc');

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function all()
    {
        $products = $this->productRepository->all();

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productRepository->adminCreate($request->validated());

        return responder()->success($product, AbstractProductTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($product_id)
    {
        $product = $this->productRepository->find($product_id);

        return responder()->success($product, ProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateProductRequest $request, $product_id)
    {
        $product = $this->productRepository->find($product_id);
        $this->productRepository->adminUpdate($product, $request->validated());

        return responder()->success(['message' => 'تم تعديل الصنف بنجاح'])->respond(Response::HTTP_OK);
    }

    public function review($product_id)
    {
        $product = $this->productRepository->find($product_id);
        $this->productRepository->adminReview($product);

        return responder()->success(['message' => 'تم مراجعة الصنف بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($product_id)
    {
        $product = $this->productRepository->find($product_id);

        DepartmentProduct::query()->where('product_id', $product->id)->delete();

        $product->update([
            'deleted_at' => Carbon::now(),
        ]);

        // $this->productRepository->adminDelete($product);

        return responder()->success(['message' => 'تم حذف الصنف بنجاح'])->respond(Response::HTTP_OK);
    }

    public function addPrice(UpdateProductPriceRequest $request, $product_id)
    {
        $product = $this->productRepository->find($product_id);
        $this->productRepository->adminaddPrice($product, $request->validated());

        return responder()->success(['message' => 'تم اضافه السعر بنجاح'])->respond(Response::HTTP_OK);
    }

    public function updatePrice(UpdateProductPriceRequest $request, $price_id)
    {
        $this->productRepository->adminUpdatePrice($price_id, $request->validated());

        return responder()->success(['message' => 'تم تعديل السعر بنجاح'])->respond(Response::HTTP_OK);
    }

    public function deletePrice($price_id)
    {
        $this->productRepository->adminDeletePrice($price_id);

        return responder()->success(['message' => 'تم حذف السعر بنجاح'])->respond(Response::HTTP_OK);
    }

    public function filterByCategory($category_id)
    {
        $category = $this->categoryRepository->find($category_id);

        $products = $category->products;

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function filterBySubCategory(SearchProductRequest $request, $subCategory_id)
    {
        $data = $request->validated();
        $data['sub_category_id'] = $subCategory_id;
        // $products = $this->productRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
        $products = $this->productRepository->getInterceptedByAttributes2($data)->when(auth()->user()->roles()->first()->id != Role::ADMIN_ROLE_ID, fn ($q) => $q->whereNull('deleted_at'))->get();

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function filterBySubCategory2(SearchProductRequest $request, $subCategory_id)
    {
        $data = $request->validated();
        $subCategory = $this->subCategoryRepository->find($subCategory_id);
        if (count($data) > 0) {
            $categories = $this->productRepository->filter($data, 'created_at', 'desc')->where(['sub_category_id' => $subCategory_id])->get();

            return responder()->success($this->productRepository->paginate($categories), AbstractProductTransformer::class)->respond(Response::HTTP_OK);
        }
        $products = $subCategory->products()->get();

        return responder()->success($products, AbstractProductTransformer::class)->respond(Response::HTTP_OK);
    }

    public function addToDepartment(AddToDepartmentRequest $request)
    {
        $this->productRepository->AddToDepartment($request->validated());

        return responder()->success(['message' => 'تم اضافة المنتجات للمنفذ بنجاح'])->respond(Response::HTTP_CREATED);
    }

    public function editProductDepartment(EditProductDepartmentRequest $request, $product_department_id)
    {
        $this->productRepository->EditProductDepartment($request->validated(), $product_department_id);

        return responder()->success(['message' => 'تم تعديل المنتجات للمنفذ بنجاح'])->respond(Response::HTTP_CREATED);
    }

    public function department(SubcategoryRequest $request, $department_id)
    {
        $data = $request->validated();
        $department = Department::find($department_id);

        if (count($data) > 0) {
            if (isset($data['sub_category_id']) && $data['sub_category_id'] != '') {
                $products = $this->productRepository->department($data, $department_id);
            } else {
                $products = Product::join('department_product', 'products.id', '=', 'department_product.product_id')
                    ->where('department_product.department_id', '=', $department_id)
                    ->whereRaw('products.name LIKE ?', ['%'.$data['name'].'%'])
                    ->select('products.*')
                    ->get();
            }

            return responder()->success($this->productRepository->paginate($products))->respond(Response::HTTP_OK);
        }
        $products = $this->productRepository->showDepartmentProducts($department_id);

        return responder()->success($this->productRepository->paginate($products))->respond(Response::HTTP_OK);
    }

    public function removeFromDepartment($product_id, Request $request)
    {
        $department_id = $request->input('department_id');
        $data = [
            'product_id' => $product_id,
            'department_id' => $department_id,
        ];

        $this->productRepository->removeFromDepartment($data);

        return responder()->success(['message' => 'تم حذف المنتج من المنفذ بنجاح'])->respond(Response::HTTP_OK);
    }

    public function addRecipe(AddRecipeToProductRequest $request)
    {
        $this->productRepository->addRecipeToProduct($request->validated());

        return responder()->success(['message' => 'تم اضافة المكونات للمنتج بنجاح'])->respond(Response::HTTP_CREATED);
    }

    public function removeRecipe($product, $recipe_in_department)
    {
        $product = $this->productRepository->removeRecipe($product, $recipe_in_department);

        return responder()->success(['message' => $product])->respond(Response::HTTP_OK);
    }

    public function subcategory(DepartmentRequest $request)
    {
        $departmentId = request()->query('department_id');
        $subcategories = Subcategory::whereHas('products', function ($query) use ($departmentId) {
            $query->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        })->get();

        return responder()->success($subcategories, AbstractSubCategoryTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getProductsCostReport(Request $request)
    {
        $data = $request->validate(['name' => 'nullable', 'category_id' => 'nullable']);

        $products = $this->productRepository->getInterceptedByAttributes2($data)->get();

        $filteredProducts = $products->map(function ($product) {
            $costPrice = CalculateProductCostPrice::calculateCostPrice($product) ?? 0;

            if ($product->price < 1.7 * $costPrice || $costPrice == 0) {
                $subcategoryName = $product->subCategory ? $product->subCategory->name : 'N/A';
                $categoryName = $product->category ? $product->category->name : 'N/A';

                return [
                    'id' => (string) $product->id,
                    'name' => $product->name,
                    'image' => $product->image ? (string) config('app.url').$product->image : '',
                    'price' => $product->price,
                    'cost_price' => number_format($costPrice, 3),
                    'type' => $product->type,
                    'status' => $product->status,
                    'description' => $product->description,
                    'estimated_price' => number_format($costPrice * 1.7, 3),
                    'subcategory_name' => $subcategoryName,
                    'category_name' => $categoryName,
                ];
            }
        })->filter();

        return responder()->success($filteredProducts)->respond(Response::HTTP_OK);
    }
}
