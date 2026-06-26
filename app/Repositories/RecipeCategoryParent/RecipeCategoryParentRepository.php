<?php

namespace App\Repositories\RecipeCategoryParent;

use App\Repositories\BaseRepository;

interface RecipeCategoryParentRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
