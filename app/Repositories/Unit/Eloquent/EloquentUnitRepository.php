<?php

namespace App\Repositories\Unit\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Unit\UnitRepository;

class EloquentUnitRepository extends EloquentBaseRepository implements UnitRepository
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
