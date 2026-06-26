<?php

namespace App\Repositories\Building\Eloquent;

use App\Models\Building;
use App\Repositories\Building\BuildingRepository;
use App\Repositories\EloquentBaseRepository;
use Illuminate\Support\Collection;

class EloquentBuildingRepository extends EloquentBaseRepository implements BuildingRepository
{
    public function __construct()
    {
        parent::__construct(new Building);
    }

    public function all($orderBy = null, $sortedBy = 'desc'): Collection
    {
        if ($orderBy != null) {
            return $this->model->orderBy($orderBy, $sortedBy)->get();
        }

        return $this->model->get();
    }

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

    public function withAvailableApartments()
    {
        return $this->model->with([
            'apartments' => function ($query) {
                $query->where('is_occupied', false);
            }
        ])->get();
    }

    public function getOccupancyStats()
    {
        return $this->model->withCount([
            'apartments',
            'apartments as occupied_apartments_count' => function ($query) {
                $query->where('is_occupied', true);
            },
            'apartments as available_apartments_count' => function ($query) {
                $query->where('is_occupied', false);
            }
        ])->get();
    }

    public function searchByName($name)
    {
        return $this
            ->model
            ->where('name', 'LIKE', "%{$name}%")
            ->orderBy('name', 'asc')
            ->get();
    }

    public function withApartments()
    {
        return $this
            ->model
            ->with('apartments')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function findWithApartments($id)
    {
        return $this->model->with([
            'apartments' => function ($query) {
                $query->orderBy('number', 'asc');
            }
        ])->findOrFail($id);
    }

    public function findMany($ids)
    {
        return $this->model->whereIn('id', $ids)->get();
    }
}
