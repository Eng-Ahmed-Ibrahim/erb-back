<?php

namespace App\Repositories\Department\Eloquent;

use App\Repositories\Department\DepartmentRepository;
use App\Repositories\EloquentBaseRepository;
use Illuminate\Support\Facades\Storage;

class EloquentDepartmentRepository extends EloquentBaseRepository implements DepartmentRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->saveImage($data['image'], 'departments_images');
        }

        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        if ($data['image']) {
            Storage::disk('public')->delete($model->image ?? 'random');
            $data['image'] = $this->saveImage($data['image'], 'departments_images');
        } else {
            unset($data['image']);
        }

        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        if ($model->image) {
            Storage::disk('public')->delete($model->image ?? 'random');
        }

        return $this->delete($model);
    }
}
