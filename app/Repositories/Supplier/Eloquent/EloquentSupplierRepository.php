<?php

namespace App\Repositories\Supplier\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Supplier\SupplierRepository;

class EloquentSupplierRepository extends EloquentBaseRepository implements SupplierRepository
{
    public function adminCreate($data)
    {
        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        return $this->delete($model);
    }
}
