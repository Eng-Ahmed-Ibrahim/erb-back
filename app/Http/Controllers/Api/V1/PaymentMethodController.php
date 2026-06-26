<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use App\Transformers\PaymentMethod\AbstractPaymentMethodTransformer;
use App\Transformers\PaymentMethod\PaymentMethodTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentMethodController extends Controller
{
    public function __construct(private PaymentMethodRepository $PaymentMethodRepository)
    {
        $this->PaymentMethodRepository = $PaymentMethodRepository;
    }

    public function index(Request $request)
    {
        // $data =  $request->input('data', )

        $data = $this->PaymentMethodRepository->allPaginated('created_at', 'desc');

        return responder()->success($data, AbstractPaymentMethodTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->PaymentMethodRepository->find($id);

        return responder()->success($data, PaymentMethodTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StorePaymentMethodRequest $request)
    {
        $data = $this->PaymentMethodRepository->adminCreate($request->validated());

        return responder()->success($data, AbstractPaymentMethodTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdatePaymentMethodRequest $request, string $id)
    {
        $model = $this->PaymentMethodRepository->find($id);
        $data = $this->PaymentMethodRepository->adminUpdate($model, $request->validated());

        return responder()->success($model, AbstractPaymentMethodTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->PaymentMethodRepository->find($id);
        $this->PaymentMethodRepository->adminDelete($model);

        return responder()->success(['message' => 'تم حذف طريقة الدفع بنجاح'])->respond(Response::HTTP_OK);
    }
}
