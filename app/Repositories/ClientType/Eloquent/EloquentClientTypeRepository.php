<?php

namespace App\Repositories\ClientType\Eloquent;

use App\Models\ClientType;
use App\Repositories\ClientType\ClientTypeRepository;
use App\Repositories\EloquentBaseRepository;

class EloquentClientTypeRepository extends EloquentBaseRepository implements ClientTypeRepository
{
    public function adminCreate($data)
    {
        $clienttype = $this->create($data);
        if (isset($data['payment_methods'])) {
            foreach ($data['payment_methods'] as $payment) {
                $clienttype->paymentMethods()->attach($payment);
            }
        }

        return $clienttype;
    }

    public function adminUpdate($model, $data)
    {
        $client = $this->update($model, $data);
        $clienttype = ClientType::findOrFail($model->id);
        if (isset($data['payment_methods'])) {
            foreach ($data['payment_methods'] as $payment) {
                $payment_check = $clienttype->paymentMethods()->wherePivot('payment_method_id', $payment)->wherePivot('client_type_id', $model->id)->exists();
                if (! $payment_check) {
                    $clienttype->paymentMethods()->attach($payment);
                }
            }
        }

        return $clienttype;
    }

    public function adminDelete($model)
    {

        return $this->delete($model);
    }
}
