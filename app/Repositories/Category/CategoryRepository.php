<?php

namespace App\Repositories\Category;

use App\Repositories\BaseRepository;

interface CategoryRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
