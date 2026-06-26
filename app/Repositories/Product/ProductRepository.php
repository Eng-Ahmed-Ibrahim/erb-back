<?php

namespace App\Repositories\Product;

use App\Repositories\BaseRepository;

interface ProductRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function adminReview($model);
    // public function adminUpdatePrice($model, $data);

    public function adminaddPrice($price_id, $data);

    public function adminDeletePrice($price_id);

    public function AddToDepartment($data);

    public function EditProductDepartment($data, $product_department_id);

    public function showDepartmentProducts($id);

    public function addRecipeToProduct($data);

    public function removeFromDepartment($data);

    public function removeRecipe($product, $data);

    public function department($data, $department_id);
}
