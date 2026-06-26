<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Repositories\Building\BuildingRepository;
use App\Transformers\Building\AbstractBuildingTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Fractal\Resource\Item;

class BuildingController extends Controller
{
    public function __construct(
        private BuildingRepository $buildingRepository
    ) {
        $this->buildingRepository = $buildingRepository;
    }

    public function index(Request $request)
    {
        $name = $request->input('name');

        if ($name) {
            $data = $this->buildingRepository->searchByName($name);
        } else {
            $data = $this->buildingRepository->all('name', 'asc');
        }

        $data = $this->buildingRepository->paginate($data);

        return responder()->success($data, AbstractBuildingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->buildingRepository->find($id);

        return responder()->success($data, AbstractBuildingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreBuildingRequest $request)
    {
        $data = $this->buildingRepository->adminCreate($request->validated());

        return responder()->success($data, AbstractBuildingTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(StoreBuildingRequest $request, string $id)
    {
        $model = $this->buildingRepository->find($id);
        $data = $this->buildingRepository->adminUpdate($model, $request->validated());

        return response()->json(['success' => true,
            'message' => 'تم التعديل بنجاح '], Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->buildingRepository->find($id);
        $this->buildingRepository->adminDelete($model);

        return response()->json(['success' => true], Response::HTTP_OK);
    }

    public function availableApartments()
    {
        $buildings = $this->buildingRepository->withAvailableApartments();

        return responder()->success($buildings, AbstractBuildingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function occupancyStats()
    {
        $stats = $this->buildingRepository->getOccupancyStats();

        return responder()->success($stats, AbstractBuildingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function apartments(string $id)
    {
        $building = $this->buildingRepository->findWithApartments($id);

        return responder()->success($building, AbstractBuildingTransformer::class)->respond(Response::HTTP_OK);
    }
}
