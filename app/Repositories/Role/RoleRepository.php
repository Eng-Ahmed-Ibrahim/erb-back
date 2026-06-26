<?php

namespace App\Repositories\Role;

use App\Repositories\BaseRepository;

interface RoleRepository extends BaseRepository
{
    public function adminShow($roleId);

    public function adminCreate($data);

    public function adminUpdate($model, $data);
}
