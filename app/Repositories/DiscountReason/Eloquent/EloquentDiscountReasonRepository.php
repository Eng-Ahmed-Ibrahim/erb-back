<?php

namespace App\Repositories\DiscountReason\Eloquent;

use App\Repositories\DiscountReason\DiscountReasonRepository;
use App\Repositories\EloquentBaseRepository;

class EloquentDiscountReasonRepository extends EloquentBaseRepository implements DiscountReasonRepository
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
