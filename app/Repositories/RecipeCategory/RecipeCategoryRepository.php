<?php

namespace App\Repositories\RecipeCategory;

use App\Repositories\BaseRepository;

interface RecipeCategoryRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function findByCategory($category_id);
}
