<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\SearchUnitRequest;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Repositories\Unit\UnitRepository;
use App\Transformers\Unit\UnitTransformer;
use Illuminate\Http\Response;

class UnitController extends Controller
{
    public function __construct(private UnitRepository $unitRepository)
    {
        $this->unitRepository = $unitRepository;
    }

    public function index(SearchUnitRequest $request)
    {
        $data = $request->validated();
        if (count($data) > 0) {
            $categories = $this->unitRepository->getInterceptedByAttributes($data, 'created_at', 'desc')->get();

            return responder()->success($this->unitRepository->paginate($categories), UnitTransformer::class)->respond(Response::HTTP_OK);
        }
        // $units = $this->unitRepository->allPaginated('created_at', 'desc');
        $units = $this->unitRepository->get();

        return responder()->success($units, UnitTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreUnitRequest $request)
    {
        $unit = $this->unitRepository->adminCreate($request->validated());

        return responder()->success($unit, UnitTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($unit_id)
    {
        $unit = $this->unitRepository->find($unit_id);

        return responder()->success($unit, UnitTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateUnitRequest $request, $unit_id)
    {

        $unit = $this->unitRepository->find($unit_id);
        $this->unitRepository->adminUpdate($unit, $request->validated());

        return responder()->success(['message' => 'تم تعديل الوحدة بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($unit_id)
    {
        $unit = $this->unitRepository->find($unit_id);
        $this->unitRepository->adminDelete($unit);

        return responder()->success(['message' => 'تم حذف الوحدة بنجاح'])->respond(Response::HTTP_OK);
    }
}
