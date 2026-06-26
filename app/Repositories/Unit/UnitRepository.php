<?php

namespace App\Repositories\Unit;

use App\Repositories\BaseRepository;

interface UnitRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
