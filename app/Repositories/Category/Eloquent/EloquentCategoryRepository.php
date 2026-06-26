<?php

namespace App\Repositories\Category\Eloquent;

use App\Repositories\Category\CategoryRepository;
use App\Repositories\EloquentBaseRepository;
use Illuminate\Support\Facades\Storage;

class EloquentCategoryRepository extends EloquentBaseRepository implements CategoryRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->saveImage($data['image'], 'categories_images');
        }

        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {

        if (isset($data['image'])) {
            if ($data['image']) {
                if ($model->image) {
                    Storage::disk('public')->delete($model->image);
                }
                $data['image'] = $this->saveImage($data['image'], 'categories_images');
            } else {
                unset($data['image']);
            }
        }

        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        if ($model->image) {
            Storage::disk('public')->delete($model->image);
        }

        return $this->delete($model);
    }
}
