<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscountReason\StoreDiscountReasonRequest;
use App\Http\Requests\DiscountReason\UpdateDiscountReasonRequest;
use App\Repositories\DiscountReason\DiscountReasonRepository;
use App\Transformers\DiscountReason\AbstractDiscountReasonTransformer;
use App\Transformers\DiscountReason\DiscountReasonTransformer;
use Illuminate\Http\Response;

class DiscountReasonController extends Controller
{
    public function __construct(private DiscountReasonRepository $DiscountReasonRepository)
    {
        $this->DiscountReasonRepository = $DiscountReasonRepository;
    }

    public function index()
    {
        $data = $this->DiscountReasonRepository->allPaginated('created_at', 'desc');

        return responder()->success($data, AbstractDiscountReasonTransformer::class)->respond(Response::HTTP_OK);

    }

    public function show(string $id)
    {
        $data = $this->DiscountReasonRepository->find($id);

        return responder()->success($data, DiscountReasonTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreDiscountReasonRequest $request)
    {
        $data = $this->DiscountReasonRepository->adminCreate($request->validated());

        return responder()->success($data, AbstractDiscountReasonTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateDiscountReasonRequest $request, string $id)
    {
        $model = $this->DiscountReasonRepository->find($id);
        $data = $this->DiscountReasonRepository->adminUpdate($model, $request->validated());

        return responder()->success($model, AbstractDiscountReasonTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->DiscountReasonRepository->find($id);
        $this->DiscountReasonRepository->adminDelete($model);

        return responder()->success(['message' => 'تم حذف سبب الخصم بنجاح'])->respond(Response::HTTP_OK);
    }
}
