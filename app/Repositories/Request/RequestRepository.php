<?php

namespace App\Repositories\Request;

use App\Repositories\BaseRepository;

interface RequestRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function changeStatus($model, $newStatus);
}
