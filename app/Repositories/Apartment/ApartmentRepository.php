<?php

namespace App\Repositories\Apartment;

use App\Repositories\BaseRepository;

interface ApartmentRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function getAvailableByBuilding($buildingId);

    public function getByRoomType($roomType);

    public function toggleOccupancy($apartmentId);

    public function markAsOccupied($apartmentId);

    public function markAsAvailable($apartmentId);
    
    public function findMany($ids);

    public function getAvailableForDateRange($fromDate, $toDate, $buildingId = null, $roomType = null);

}