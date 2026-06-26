<?php

namespace App\Repositories\Client;

use App\Repositories\BaseRepository;

interface ClientRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);
}
