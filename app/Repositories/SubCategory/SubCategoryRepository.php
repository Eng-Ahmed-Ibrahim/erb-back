<?php

namespace App\Repositories\SubCategory;

use App\Repositories\BaseRepository;

interface SubCategoryRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
