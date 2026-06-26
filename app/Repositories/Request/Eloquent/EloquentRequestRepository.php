<?php

namespace App\Repositories\Request\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Request\RequestRepository;

class EloquentRequestRepository extends EloquentBaseRepository implements RequestRepository
{
    public function adminCreate($data)
    {
        $data['user_id'] = auth('api')->user()->id;
        $data['status'] = 'pending';
        $data['from_department_id'] = auth('api')->user()->department->id;
        $requestData = $this->create($data);

        foreach ($data['recipes'] as $recipe) {
            $requestData->recipes()->attach($recipe['id'], [
                'quantity' => $recipe['quantity'],

            ]);
        }

        return $requestData;
    }

    public function adminUpdate($request, $data)
    {
        // $data['status'] = 'pending';

        // $request = $request->update($data);

        $request->recipes()->detach();
        foreach ($data['recipes'] as $recipe) {
            $request->recipes()->attach($recipe['id'], [
                'quantity' => $recipe['quantity'],

            ]);
        }

        return $request;
    }

    public function adminDelete($model)
    {

        $model->recipes()->detach();

        return $this->delete($model);
    }

    public function changeStatus($model, $newStatus)
    {
        $model->status = $newStatus;
        $model->save();

        return $model;
    }
}
