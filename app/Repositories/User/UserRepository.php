<?php

namespace App\Repositories\User;

use App\Repositories\BaseRepository;

interface UserRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminShow($data);

    public function updateProfile($data);

    public function updatePassword($data);
}
