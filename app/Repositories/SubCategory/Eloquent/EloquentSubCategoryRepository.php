<?php

namespace App\Repositories\SubCategory\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use Illuminate\Support\Facades\Storage;

class EloquentSubCategoryRepository extends EloquentBaseRepository implements SubCategoryRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image'], 'sub_categories_images');
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
                $data['image'] = $this->saveImage($data['image'], 'sub_categories_images');
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
