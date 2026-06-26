<?php

namespace App\Repositories\RecipeCategory\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\RecipeCategory\RecipeCategoryRepository;
use Illuminate\Support\Facades\Storage;

class EloquentRecipeCategoryRepository extends EloquentBaseRepository implements RecipeCategoryRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image'], 'recipe_categories_images');
        }

        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        if (isset($data['image'])) {
            if ($data['image']) {
                if ($model->image) {
                    Storage::disk('public')->delete($model->image);
                }
                $data['image'] = $this->saveImage($data['image'], 'recipe_categories_images');
            } else {
                unset($data['image']);
            }
        }

        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        if ($model->image) {
            Storage::disk('public')->delete($model->image);
        }

        return $this->delete($model);
    }

    public function findByCategory($category_id)
    {
        return $this->model->where('category_id', $category_id)->get();
    }
}
