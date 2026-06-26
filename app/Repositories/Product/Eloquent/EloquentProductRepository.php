<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\ClientType;
use App\Models\Department;
use App\Models\DepartmentProduct;
use App\Models\Price;
use App\Models\Product;
use App\Models\Recipe;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Product\ProductRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Arr;
class EloquentProductRepository extends EloquentBaseRepository implements ProductRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image'], 'products_images');
        }

        $product = $this->create($data);

        if (isset($data['price'])) {
            $dprice = new Price;
            $dprice->name = 'سعر التكلفة';
            $dprice->price = $data['price'];
            $dprice->product_id = $product->id;
            $dprice->default = 1;

            $dprice->save();
            // $this->create($price);
        }
        if (isset($data['prices'])) {
            foreach ($data['prices'] as $price) {
                $clientType = ClientType::findOrFail($price['client_type_id']);
                $dprice = new Price;
                $dprice->name = $clientType->name;
                $dprice->price = $data['price'] + $price['profit'];
                $dprice->product_id = $product->id;
                $dprice->client_type_id = $price['client_type_id'];
                $dprice->client_id = $price['client_id'];
                $dprice->service = $price['service'];
                $dprice->profit = $price['profit'];
                $dprice->default = 0;
                $dprice->save();
                // $this->create($price);
            }
        }

        return $product;
    }

    // TODO: update prices or delete prices

    public function updatePrice($data) {}

    public function adminUpdate($model, $data)
    {
        if (isset($data['image']) && $data['image'] && $data['image']) {
            if ($data['image'] != $model->image) {
                if ($model->image) {
                    Storage::disk('public')->delete($model->image);
                }
                $data['image'] = $this->saveImage($data['image'], 'products_images');
            }
        } else {
            unset($data['image']);
        }
        if (isset($data['price'])) {
            $dprice = Price::where(['product_id' => $model->id, 'default' => 1])->first();
            if (! $dprice) {
                $dprice = new Price;
                $dprice->name = 'سعر التكلفة';
                $dprice->product_id = $model->id;
                $dprice->default = 1;
            }
            $dprice->price = $data['price'];
            $dprice->save();
        }
        // $model->status = 0;
        $model->save();

        return $this->update($model, $data);
    }

    public function adminReview($model)
    {
        $model->status = 1;
        $model->save();

        return $model;
    }

    public function adminDelete($model)
    {
        if (isset($model->image)) {
            Storage::disk('public')->delete($model->image);
        }

        return $this->delete($model);
    }

    public function adminaddPrice($model, $data)
    {
        $dprice = new Price;
        $dprice->name = $data['name'];
        $dprice->price = $data['price'];
        $dprice->client_type_id = $data['client_type_id'];
        $dprice->client_id = isset($data['client_id']) ? $data['client_id'] : null;
        $dprice->service = $data['service'];
        $dprice->profit = $data['profit'];
        $dprice->product_id = $model->id;
        $dprice->save();
        $model->status = 0;
        $model->save();
    }

    // public function adminUpdatePrice($price_id, $data)
    // {
    //     $dprice = Price::findOrFail($price_id);

    //     $dprice->name = $price['name'] ? $price['name'] : $dprice->name;
    //     $dprice->price = $price['price'] ? $price['price'] : $dprice->price;
    //     $dprice->service = $price['service'] ? $price['service'] : $dprice->service;
    //     $dprice->profit = $price['profit'] ? $price['profit'] : $dprice->profit;
    //     $dprice->client_type_id = $price['client_type_id'] ? $price['client_type_id'] : $dprice->client_type_id;
    //     $dprice->client_id = $price['client_id'] ? $price['client_id'] : $dprice->client_id;
    //     $dprice->save();
    //     $model = Product::find($dprice->product_id);
    //     $model->status = 0;
    //     $model->save();
    // }

    public function adminDeletePrice($price_id)
    {
        $model = Price::findOrFail($price_id);

        return $this->delete($model);
    }

    public function AddToDepartment($data)
    {

        $department = Department::with('products')->findOrFail($data['department_id']);
        foreach ($data['products'] as $product) {
            $existingProduct = $department->products()->find($product['product_id']);
            if ($existingProduct) {
                $existingProduct->pivot->quantity += $product['quantity'];
                $existingProduct->pivot->save();
            } else {
                $department->products()->attach($product['product_id'], ['quantity' => $product['quantity']]);
            }
        }

        return $department->products;
    }

    public function EditProductDepartment($data, $product_department_id)
    {
        $department = Department::with('products')->findOrFail($data['department_id']);
        $existingProduct = $department->products()->wherePivot('id', $product_department_id)->first();
        if ($existingProduct) {
            $existingProduct->pivot->quantity = $data['quantity'];
            $existingProduct->pivot->save();
        }

        return $department->products;
    }

    // public function addRecipeToProduct($data)
    // {
    //     $product = Product::with('recipes')->findOrFail($data['product_id']);
    //     foreach ($data['recipes'] as $recipe) {
    //         $product->recipes()->attach($recipe['recipe_id'], ['quantity' => $recipe['quantity']]);
    //     }

    //     return true;
    // } here i wanan when the data conatinus e duplicate reords with the same recipe_id to sum thier qualitity to store thelatest 


    // public function addRecipeToProduct($data)
    // {
    //     DB::beginTransaction();
    
    //     try {
    //         $product = Product::with('recipes')->findOrFail($data['product_id']);
    
    //         foreach ($data['recipes'] as $recipe) {
    //             $product->recipes()->syncWithoutDetaching([
    //                 $recipe['recipe_id'] => ['quantity' => $recipe['quantity']]
    //             ]);
    //         }
    
    //         DB::commit();
    //         return true;
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e; // Or return error message
    //     }
    // }
    

    
    public function addRecipeToProduct($data)
    {
        DB::beginTransaction();
    
        try {
            $product = Product::with('recipes')->findOrFail($data['product_id']);
    
            // Use Laravel Collection to group and sum quantities
            $mergedRecipes = collect($data['recipes'])
                ->groupBy('recipe_id')
                ->mapWithKeys(function ($items, $recipeId) {
                    $totalQty = $items->sum('quantity');
                    return [$recipeId => ['quantity' => $totalQty]];
                });
    
            // Sync merged data in one pass
            $product->recipes()->syncWithoutDetaching($mergedRecipes->toArray());
    
            DB::commit();
            return true;
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    

    public function showDepartmentProducts($id)
    {

        $department = Department::with('products')->findOrFail($id);

        return $department->products;
    }

    public function removeFromDepartment($data)
    {

        $dd = DepartmentProduct::where('product_id', $data['product_id'])
            ->where('department_id', $data['department_id'])
            ->delete();
    }

    public function removeRecipe($productId, $data)
    {
        $product = Product::find($productId);
        $recipe = Recipe::find($data);

        if ($product && $recipe) {
            if ($product->recipes()->where('recipe_id', $recipe->id)->exists()) {
                $product->removeRecipe($data);

                return 'Recipe removed from product.';
            } else {
                return 'product not have this recipe';
            }
        } else {
            return 'Product or Recipe not found.';
        }
    }

    public function department($data, $department_id)
    {
        $products = $this->where('sub_category_id', $data['sub_category_id'], '=')
            ->whereHas('departments', function ($query) use ($department_id) {
                $query->where('department_id', $department_id);
            })->get();

        return $products;
    }
}
