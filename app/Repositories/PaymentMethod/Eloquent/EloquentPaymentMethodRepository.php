<?php

namespace App\Repositories\PaymentMethod\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use Illuminate\Support\Facades\Storage;

class EloquentPaymentMethodRepository extends EloquentBaseRepository implements PaymentMethodRepository
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
