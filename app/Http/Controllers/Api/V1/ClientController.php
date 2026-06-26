<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\ClientTypeClient;
use App\Repositories\Client\ClientRepository;
use App\Transformers\Client\AbstractClientTransformer;
use App\Transformers\Client\ClientTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function __construct(
        private ClientRepository $ClientRepository
    ) {
        $this->ClientRepository = $ClientRepository;
    }

    public function index(Request $request)
    {
        $name = $request->input('name', '');
        $data = $this->ClientRepository->getInterceptedByAttributes(['name' => $name], 'created_at', 'desc');
        $data = $this->ClientRepository->paginate($data);

        return responder()->success($data, AbstractClientTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->ClientRepository->find($id);

        return responder()->success($data, ClientTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreClientRequest $request)
    {
        $data = $this->ClientRepository->adminCreate($request->validated());

        if ($data) {
            ClientTypeClient::create([
                'client_id' => $data->id,
                'client_type_id' => $data['client_type_id'],
            ]);
        }

        return responder()->success($data, AbstractClientTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateClientRequest $request, string $id)
    {
        $model = $this->ClientRepository->find($id);
        $data = $this->ClientRepository->adminUpdate($model, $request->validated());

        return responder()->success($data, AbstractClientTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->ClientRepository->find($id);
        $this->ClientRepository->adminDelete($model);

        return responder()->success(['message' => 'تم حذف العميل بنجاح'])->respond(Response::HTTP_OK);
    }
}
