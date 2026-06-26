<?php

namespace App\Repositories\DiscountReason;

use App\Repositories\BaseRepository;

interface DiscountReasonRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
