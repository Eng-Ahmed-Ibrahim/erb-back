<?php

namespace App\Repositories\Payable;

use App\Repositories\BaseRepository;

interface PayableRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
