<?php

namespace App\Repositories\Department;

use App\Repositories\BaseRepository;

interface DepartmentRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
