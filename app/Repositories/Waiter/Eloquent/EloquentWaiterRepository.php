<?php

namespace App\Repositories\Waiter\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Waiter\WaiterRepository;
use Illuminate\Support\Facades\Storage;

class EloquentWaiterRepository extends EloquentBaseRepository implements WaiterRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->saveImage($data['image'], 'waiters_images');
        }

        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        if ($data['image']) {
            Storage::disk('public')->delete($model->image ?? 'random');
            $data['image'] = $this->saveImage($data['image'], 'waiters_images');
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
