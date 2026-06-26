<?php

namespace App\Repositories\Waiter;

use App\Repositories\BaseRepository;

interface WaiterRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
