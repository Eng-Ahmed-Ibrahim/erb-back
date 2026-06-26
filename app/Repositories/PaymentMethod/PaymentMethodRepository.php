<?php

namespace App\Repositories\PaymentMethod;

use App\Repositories\BaseRepository;

interface PaymentMethodRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
