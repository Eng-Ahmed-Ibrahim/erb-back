<?php

namespace App\Repositories\RecipeCategoryParent\Eloquent;

use App\Models\WarehouseSections;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\RecipeCategoryParent\RecipeCategoryParentRepository;
use Illuminate\Support\Facades\Storage;

class EloquentRecipeCategoryParentRepository extends EloquentBaseRepository implements RecipeCategoryParentRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image'], 'recipe_categories_images');
        }

        $wareHouseSection = WarehouseSections::create([
            'name' => $data['name'],
        ]);
        $data['warehouse_section_id'] = $wareHouseSection['id'];

        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        if (isset($data['image'])) {
            if ($data['image']) {
                Storage::disk('public')->delete($model->image);
                $data['image'] = $this->saveImage($data['image'], 'recipe_categories_images');
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
