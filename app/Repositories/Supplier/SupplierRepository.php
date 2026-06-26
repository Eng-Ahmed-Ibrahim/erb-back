<?php

namespace App\Repositories\Supplier;

use App\Repositories\BaseRepository;

interface SupplierRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
