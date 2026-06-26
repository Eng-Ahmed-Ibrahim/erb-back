<?php

namespace App\Repositories\Recipe;

use App\Repositories\BaseRepository;

interface RecipeRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function findByCategory($recipe_category_id);
}
