<?php

namespace App\Repositories\Visitor\Eloquent;

use App\Models\Visitor;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Visitor\VisitorRepository;

class EloquentVisitorRepository extends EloquentBaseRepository implements VisitorRepository
{
    public function __construct()
    {
        parent::__construct(new Visitor);
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

    public function findByIdNumber($idNumber)
    {
        return $this->model->where('id_number', $idNumber)->first();
    }

    public function getByVisitorType($visitorType)
    {
        return $this->model->where('visitor_type', $visitorType)->get();
    }

    public function searchByName($name)
    {
        return $this->model->where('name', 'LIKE', '%' . $name . '%')->get();
    }
}