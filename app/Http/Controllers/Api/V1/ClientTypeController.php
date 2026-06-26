<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientType\StoreClientTypeRequest;
use App\Http\Requests\ClientType\UpdateClientTypeRequest;
use App\Models\ClientTypeDepartment;
use App\Repositories\ClientType\ClientTypeRepository;
use App\Transformers\ClientType\AbstractClientTypeTransformer;
use App\Transformers\ClientType\ClientTypeTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientTypeController extends Controller
{
    public function __construct(
        private ClientTypeRepository $ClientTypeRepository
    ) {
        $this->ClientTypeRepository = $ClientTypeRepository;
    }

    public function index()
    {
        $data = $this->ClientTypeRepository->getInterceptedByAttributes([], 'created_at', 'desc');

        return responder()->success($data, AbstractClientTypeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function payment_method($type, Request $request)
    {
        $department_id = $request->input('department_id', '');
        $data = $this->ClientTypeRepository->whereHas('paymentMethods', 'payment_method_id', $type)
            ->when($department_id != '', fn ($query) => $query->whereIN('id', ClientTypeDepartment::where('department_id', $department_id)->pluck('client_type_id')->toArray()))
            ->get();

        return responder()->success($data, AbstractClientTypeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->ClientTypeRepository->find($id);

        return responder()->success($data, ClientTypeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreClientTypeRequest $request)
    {
        $data = $this->ClientTypeRepository->adminCreate($request->validated());

        return responder()->success($data, AbstractClientTypeTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateClientTypeRequest $request, string $id)
    {
        $model = $this->ClientTypeRepository->find($id);
        $data = $this->ClientTypeRepository->adminUpdate($model, $request->validated());

        return responder()->success($model, AbstractClientTypeTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->ClientTypeRepository->find($id);
        $this->ClientTypeRepository->adminDelete($model);

        return responder()->success(['message' => 'تم حذف نوع العميل بنجاح'])->respond(Response::HTTP_OK);
    }

    public function getDepartmentsWithDiscount(Request $request)
    {
        $data = $request->input('data', []);

        if (isset($data['client_type_id'])) {
            $departments = ClientTypeDepartment::where('client_type_id', $data['client_type_id'])->pluck('department_id')->toArray();

            return responder()->success($departments ?? [])->respond(Response::HTTP_OK);
        }

        return responder()->error('نوع العميل مطلوب')->respond(Response::HTTP_BAD_REQUEST);
    }

    public function updateDepartmentsWithDiscount(Request $request)
    {
        if ($request->client_type_id) {
            $departmentIds = ClientTypeDepartment::where('client_type_id', $request->client_type_id)->pluck('department_id')->toArray();

            $to_insert = array_diff($request->departments, $departmentIds ?? []);

            $to_delete = array_diff($departmentIds ?? [], $request->departments);

            foreach ($to_insert as $department_id) {
                ClientTypeDepartment::create([
                    'client_type_id' => $request->client_type_id,
                    'department_id' => $department_id,
                ]);
            }

            foreach ($to_delete as $department_id) {
                ClientTypeDepartment::where('client_type_id', $request->client_type_id)
                    ->where('department_id', $department_id)
                    ->delete();
            }

            return responder()->success(['message' => 'نم التعديل بنجاح'])->respond(Response::HTTP_OK);
        }

        return responder()->success(['message' => 'حدث خطأ أثناء التعديل'])->respond(Response::HTTP_BAD_REQUEST);
    }
}
