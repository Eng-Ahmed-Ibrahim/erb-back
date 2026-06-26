<?php

namespace App\Repositories\Apartment\Eloquent;

use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Apartment\ApartmentRepository;
use Illuminate\Support\Collection;

class EloquentApartmentRepository extends EloquentBaseRepository implements ApartmentRepository
{
    public function __construct()
    {
        parent::__construct(new Apartment);
    }

    /**
     * Override the all() method to ensure it returns a Collection
     */
    public function all($orderBy = null, $sortedBy = 'desc'): Collection
    {
        if ($orderBy != null) {
            return $this->model->with('building')->orderBy($orderBy, $sortedBy)->get();
        }

        return $this->model->with('building')->get();
    }

    public function adminCreate($data)
    {
        // Extract pricing data
        $pricesData = $data['prices'] ?? [];
        unset($data['prices']);

        // Create the apartment
        $apartment = $this->create($data);

        // Create apartment prices
        if (!empty($pricesData)) {
            foreach ($pricesData as $priceData) {
                $apartment->prices()->create($priceData);
            }
        }

        // Load relationships for return
        $apartment->load(['building', 'prices.clientType']);

        return $apartment;
    }

    public function adminUpdate($model, $data)
    {
        // Extract pricing data
        $pricesData = $data['prices'] ?? [];
        unset($data['prices']);

        // Update the apartment
        $apartment = $this->update($model, $data);

        // Update apartment prices
        if (!empty($pricesData)) {
            // Delete existing prices
            $apartment->prices()->each(function ($price) {
                $price->delete();
            });

            // Create new prices
            foreach ($pricesData as $priceData) {
                $apartment->prices()->create($priceData);
            }
        }

        // Load relationships for return
        $apartment->load(['building', 'prices.clientType']);

        return $apartment;
    }

    public function adminDelete($model)
    {
        return $this->delete($model);
    }

    public function getAvailableByBuilding($buildingId)
    {
        return $this->model->where('building_id', $buildingId)
            ->where('is_occupied', false)
            ->with('building')
            ->orderBy('apartment_number', 'asc')
            ->get();
    }

    public function getByRoomType($roomType)
    {
        return $this->model->where('room_type', $roomType)
            ->with('building')
            ->orderBy('apartment_number', 'asc')
            ->get();
    }

    public function toggleOccupancy($apartmentId)
    {
        $apartment = $this->find($apartmentId);
        $apartment->is_occupied = !$apartment->is_occupied;
        $apartment->save();
        return $apartment;
    }

    public function markAsOccupied($apartmentId)
    {
        $apartment = $this->find($apartmentId);
        $apartment->is_occupied = true;
        $apartment->save();
        return $apartment;
    }

    public function markAsAvailable($apartmentId)
    {
        $apartment = $this->find($apartmentId);
        $apartment->is_occupied = false;
        $apartment->save();
        return $apartment;
    }

    public function findMany($ids)
    {
        return $this->model->whereIn('id', $ids)
            ->with('building')
            ->get();
    }

    /**
     * Override getByAttributes to include building relationship
     */
    public function getByAttributes(array $attributes, $orderBy = null, $sortOrder = 'asc'): Collection
    {
        $query = $this->model->with('building');

        foreach ($attributes as $field => $value) {
            $query->where($field, $value);
        }

        if ($orderBy) {
            $query->orderBy($orderBy, $sortOrder);
        }

        return $query->get();
    }

    public function getAvailableForDateRange($fromDate, $toDate, $buildingId = null, $roomType = null)
    {
        $query = $this->model->newQuery()
            ->where('is_active', 1)
            ->with('building');

        // Apply building filter
        if ($buildingId !== null && $buildingId !== '') {
            $query->where('building_id', $buildingId);
        }

        // Apply room type filter
        if ($roomType !== null && $roomType !== '') {
            $query->where('room_type', $roomType);
        }

        // Filter out apartments with conflicting bookings
        $statusActive = Booking::STATUS_ACTIVE;
        $statusPending = Booking::STATUS_PENDING;

        $query->whereDoesntHave('bookings', function ($bookingQuery) use ($fromDate, $toDate, $statusActive, $statusPending) {
            $bookingQuery->whereIn('status', [$statusActive, $statusPending])
                ->where(function ($subQuery) use ($fromDate, $toDate) {
                    $subQuery->where(function ($q) use ($fromDate, $toDate) {
                        // Booking starts during the requested period
                        $q->whereBetween('arrival_datetime', [$fromDate, $toDate]);
                    })->orWhere(function ($q) use ($fromDate, $toDate) {
                        // Booking ends during the requested period
                        $q->whereBetween('checkout_datetime', [$fromDate, $toDate]);
                    })->orWhere(function ($q) use ($fromDate, $toDate) {
                        // Booking spans the entire requested period
                        $q->where('arrival_datetime', '<=', $fromDate)
                            ->where('checkout_datetime', '>=', $toDate);
                    });
                });
        });

        return $query->orderBy('apartment_number', 'asc')->get();
    }
}