<?php

namespace App\Repositories\Client\Eloquent;

use App\Models\ClientTypeClient;
use App\Models\WorkerSallary;
use App\Repositories\Client\ClientRepository;
use App\Repositories\EloquentBaseRepository;

class EloquentClientRepository extends EloquentBaseRepository implements ClientRepository
{
    public function adminCreate($data)
    {
        $client = $this->create($data);
        if (isset($data['sallary']) && $data['sallary'] != '') {
            $sallary = new WorkerSallary;
            $sallary->client_id = $client->id;
            $sallary->sallary = $data['sallary'];
            $sallary->incentives = isset($data['incentives']) ? $data['incentives'] : '';
            $sallary->save();
        }

        return $client;
    }

    public function adminUpdate($model, $data)
    {

        if (isset($data['client_type_id'])) {
            ClientTypeClient::where('client_id', $model->id)
                ->delete();
            $types = array_map('trim', explode(',', $data['client_type_id']));
            unset($data['client_type_id']);

            foreach ($types as $type) {
                ClientTypeClient::create([
                    'client_type_id' => $type,
                    'client_id' => $model->id,
                ]);
            }
        }

        $this->update($model, $data);
        $sallary = WorkerSallary::where('client_id', $model->id)->first();
        if (isset($data['sallary']) && $data['sallary'] != null && $data['sallary'] != 'null') {
            if (! $sallary) {
                $sallary = new WorkerSallary;
                $sallary->client_id = $model->id;
            }
            $sallary->sallary = $data['sallary'];
            $sallary->incentives = isset($data['incentives']) ? $data['incentives'] : '';
            $sallary->save();
        }

        return $model;
    }

    public function adminDelete($model)
    {
        ClientTypeClient::where('client_id', $model->id)
            ->delete();

        return $this->delete($model);
    }
}
