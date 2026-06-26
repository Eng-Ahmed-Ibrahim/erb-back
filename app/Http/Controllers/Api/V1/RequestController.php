<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Request\SearchRequest;
use App\Http\Requests\Request\StoreRequest;
use App\Http\Requests\Request\UpdateRequest;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Request\RequestRepository;
use App\Repositories\User\UserRepository;
use App\Transformers\Request\RequestTransformer;
use Illuminate\Http\Response;

class RequestController extends Controller
{
    public function __construct(
        private RequestRepository $requestRepository,
        private UserRepository $userRepository,
        private DepartmentRepository $departmentRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->userRepository = $userRepository;
        $this->departmentRepository = $departmentRepository;
    }

    public function index(SearchRequest $request)
    {
        $data = $request->validated();
        if (isset($data['date']) && $data['date']) {
            $date = $data['date'];
            unset($data['date']);
        }
        if (count($data) > 0) {
            $requests = $this->requestRepository->getInterceptedByAttributes($data, 'created_at', 'desc');
            if (isset($date['from']) && isset($date['to'])) {
                $requests = $requests->whereBetween('created_at', [$date['from'], $date['to']]);
            }

            return responder()->success($this->requestRepository->paginate($requests), RequestTransformer::class)->respond(Response::HTTP_OK);
        }
        $requests = $this->requestRepository->allPaginated('created_at', 'desc');
        if (isset($date['from']) && isset($date['to'])) {
            $requests = $requests->whereBetween('created_at', [$date['from'], $date['to']]);
        }

        return responder()->success($requests, RequestTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $request = $this->requestRepository->adminCreate($data);

        return responder()->success($request, RequestTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function show($request_id)
    {
        $requests = $this->requestRepository->find($request_id);

        return responder()->success($requests, RequestTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateRequest $request, $request_id)
    {
        $requestModel = $this->requestRepository->find($request_id);
        $this->requestRepository->adminUpdate($requestModel, $request->validated());

        return responder()->success(['message' => 'تم تعديل الطلب بنجاح'])->respond(Response::HTTP_OK);
    }

    public function delete($request_id)
    {
        $requests = $this->requestRepository->find($request_id);
        $this->requestRepository->adminDelete($requests);

        return responder()->success(['message' => 'تم حذف الطلب بنجاح'])->respond(Response::HTTP_OK);
    }

    public function filterByUsaer($user_id)
    {
        $user = $this->userRepository->find($user_id);
        $requsts = $user->requests()->orderBy('created_at', 'desc')->get();

        return responder()->success($requsts, RequestTransformer::class)->respond(Response::HTTP_OK);
    }

    public function filterByDepartment($department_id)
    {
        $department = $this->departmentRepository->find($department_id);
        $requsts = $department->requests()->orderBy('created_at', 'desc')->get();

        return responder()->success($requsts, RequestTransformer::class)->respond(Response::HTTP_OK);
    }

    public function changeStatus($request_id, $status)
    {
        $request = $this->requestRepository->find($request_id);
        $request = $this->requestRepository->changeStatus($request, $status);

        return responder()->success(['message' => 'تم تعديل الحاله بنجاح'])->respond(Response::HTTP_OK);
    }

    public function doneAllApproveRequest()
    {
        $requests = $this->requestRepository->where('status', 'approved', '=')->get()->toArray();
        foreach ($requests as $request) {
            $request->update(['status' => 'done']);
        }

        return view('pdf.requests.requests', $requests);
        // return $pdf->download('requests.pdf');

        return responder()->success($requests, RequestTransformer::class)->respond(Response::HTTP_OK);
    }
}
