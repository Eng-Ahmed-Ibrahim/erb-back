<?php

namespace App\Repositories\Visitor;

use App\Repositories\BaseRepository;

interface VisitorRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function findByIdNumber($idNumber);

    public function getByVisitorType($visitorType);

    public function searchByName($name);
}