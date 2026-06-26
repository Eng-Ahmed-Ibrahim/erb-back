<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visitor\StoreVisitorRequest;
use App\Http\Requests\Visitor\UpdateVisitorRequest;
use App\Repositories\Visitor\VisitorRepository;
use App\Transformers\Visitor\AbstractVisitorTransformer;
use App\Transformers\Visitor\VisitorTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Visitor;
use App\Http\Requests\Visitor\SearchVisitorRequest;

class VisitorController extends Controller
{
    public function __construct(
        private VisitorRepository $visitorRepository
    ) {
        $this->visitorRepository = $visitorRepository;
    }

    public function index(Request $request)
    {
        $name = $request->input('name', '');
        $visitorType = $request->input('visitor_type', '');

        $filters = [];
        if ($name) {
            $filters['name'] = $name;
        }
        if ($visitorType) {
            $filters['visitor_type'] = $visitorType;
        }

        if (!empty($filters)) {
            $data = $this->visitorRepository->getInterceptedByAttributes($filters, 'created_at', 'desc');
        } else {
            $data = $this->visitorRepository->all('created_at', 'desc');
        }

        $data = $this->visitorRepository->paginate($data);

        return responder()->success($data, AbstractVisitorTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->visitorRepository->find($id);

        return responder()->success($data, VisitorTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreVisitorRequest $request)
    {
        $data = $this->visitorRepository->adminCreate($request->validated());

        return responder()->success($data, AbstractVisitorTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateVisitorRequest $request, string $id)
    {
        $model = $this->visitorRepository->find($id);
        $data = $this->visitorRepository->adminUpdate($model, $request->validated());

        return responder()->success($data, AbstractVisitorTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->visitorRepository->find($id);
        $this->visitorRepository->adminDelete($model);

        return responder()->success([])->respond(Response::HTTP_OK);
    }

    public function searchByIdNumber(Request $request)
    {
        $idNumber = $request->input('id_number');

        if (!$idNumber) {
            return responder()->error('id_number parameter is required')->respond(Response::HTTP_BAD_REQUEST);
        }

        $visitor = $this->visitorRepository->findByIdNumber($idNumber);

        if (!$visitor) {
            return responder()->error('Visitor not found')->respond(Response::HTTP_NOT_FOUND);
        }

        return responder()->success($visitor, VisitorTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getByType(Request $request, string $type)
    {
        $visitors = $this->visitorRepository->getByVisitorType($type);

        return responder()->success($visitors, AbstractVisitorTransformer::class)->respond(Response::HTTP_OK);
    }

    /**
     * Search visitors by ID number
     *
     * @param SearchVisitorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchVisitors(SearchVisitorRequest $request)
    {
        $idNumber = $request->get('id_number');

        $visitors = Visitor::where('id_number', 'like', "%{$idNumber}%")
            ->select('id', 'id_number', 'name', 'phone', 'nationality', 'client_type_id', 'id_type', 'emergency_contact')
            ->whereIn('id', function ($query) {
                $query->select(\DB::raw('MIN(id)'))
                    ->from('visitors')
                    ->groupBy('name', 'id_number', 'phone');
            })
            ->limit(10)
            ->get();

        return responder()->success($visitors, AbstractVisitorTransformer::class)->respond(Response::HTTP_OK);
    }
}