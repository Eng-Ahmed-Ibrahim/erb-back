<?php

namespace App\Repositories\Building;

use App\Repositories\BaseRepository;

interface BuildingRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function withAvailableApartments();

    public function getOccupancyStats();
    
    public function searchByName($name);

    public function withApartments();

    public function findWithApartments($id);

    public function findMany($ids);

}