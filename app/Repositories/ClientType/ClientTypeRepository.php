<?php

namespace App\Repositories\ClientType;

use App\Repositories\BaseRepository;

interface ClientTypeRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
