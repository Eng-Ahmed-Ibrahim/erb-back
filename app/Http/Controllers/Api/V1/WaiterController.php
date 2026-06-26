<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Waiter\SearchWaiterRequest;
use App\Http\Requests\Waiter\StoreWaiterRequest;
use App\Http\Requests\Waiter\UpdateWaiterRequest;
use App\Http\Requests\Waiter\WaiterOrders;
use App\Models\Waiter;
use App\Repositories\Waiter\WaiterRepository;
use App\Transformers\Order\OrderTransformer;
use App\Transformers\Waiter\AbstractWaiterTransformer;
use App\Transformers\Waiter\WaiterTransformer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class WaiterController extends Controller
{
    public function __construct(private WaiterRepository $waiterRepository)
    {
        $this->waiterRepository = $waiterRepository;
    }

    public function index(SearchWaiterRequest $request)
    {
        $data = $request->validated();
        if (count($data) > 0) {
            $categories = $this->waiterRepository->getInterceptedByAttributes($data, 'created_at', 'desc');

            return responder()->success($this->waiterRepository->paginate($categories), AbstractWaiterTransformer::class)->respond(Response::HTTP_OK);
        }
        $waiters = $this->waiterRepository->allPaginated('created_at', 'desc');

        return responder()->success($waiters, AbstractWaiterTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show($waiter_id)
    {
        $waiter = $this->waiterRepository->find($waiter_id);

        return responder()->success($waiter, WaiterTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreWaiterRequest $request)
    {
        $waiter = $this->waiterRepository->adminCreate($request->validated());

        Cache::forget('waiters');
        Cache::rememberForever('waiters', fn() => Waiter::get());

        return responder()
            ->success($waiter, AbstractWaiterTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }
    // public function store(StoreWaiterRequest $request)
    // {
    //     $waiter = $this->waiterRepository->adminCreate($request->validated());

    //     return responder()->success($waiter, AbstractWaiterTransformer::class)->respond(Response::HTTP_CREATED);
    // }

    
    public function update(UpdateWaiterRequest $request, $waiter_id)
    {
        $waiter = $this->waiterRepository->find($waiter_id);

        $this->waiterRepository->adminUpdate($waiter, $request->validated());

        Cache::forget('waiters');
        Cache::rememberForever('waiters', fn() => Waiter::get());

        return responder()
            ->success(['message' => 'تم تعديل الويتر بنجاح'])
            ->respond(Response::HTTP_OK);
    }
    // public function update(UpdateWaiterRequest $request, $waiter_id)
    // {
    //     $waiter = $this->waiterRepository->find($waiter_id);
    //     $this->waiterRepository->adminUpdate($waiter, $request->validated());

    //     return responder()->success(['message' => 'تم تعديل الويتر بنجاح'])->respond(Response::HTTP_OK);
    // }


    public function delete($waiter_id)
    {
        $waiter = $this->waiterRepository->find($waiter_id);

        if ($waiter->orders->count() > 0) {
            return responder()
                ->error("can't_delete", 'الويتر نفذ طلبات قبل ذالك لذا لا يمكننا حذفه')
                ->respond(Response::HTTP_BAD_REQUEST);
        }

        $this->waiterRepository->adminDelete($waiter);

        Cache::forget('waiters');
        Cache::rememberForever('waiters', fn() => Waiter::get());

        return responder()
            ->success(['message' => 'تم حذف الويتر بنجاح'])
            ->respond(Response::HTTP_OK);
    }
    // public function delete($waiter_id)
    // {
    //     $waiter = $this->waiterRepository->find($waiter_id);
    //     if ($waiter->orders->count() > 0) {
    //         return responder()->error("can't_delete", 'الويتر نفذ طلبات قبل ذالك لذا لا يمكننا حذفه')->respond(Response::HTTP_BAD_REQUEST);
    //     }
    //     $this->waiterRepository->adminDelete($waiter);

    //     return responder()->success(['message' => 'تم حذف الويتر بنجاح'])->respond(Response::HTTP_OK);
    // }


    public function getAllWaiter()
    {
        $waiters = Cache::rememberForever('waiters', function () {
            return Waiter::get();
        });

        return response()->json([
            "status" => 200,
            "success" => true,
            "data" => $waiters
        ]);
    }
    // public function getAllWaiter()
    // {
    //     $waiters = $this->waiterRepository->all();
    //     return responder()->success($waiters, AbstractWaiterTransformer::class)->respond(Response::HTTP_OK);
    // }

    public function waiterOrders(WaiterOrders $request, $waiter_id)
    {
        $waiter = $this->waiterRepository->find($waiter_id);
        $data = $request->validated();
        $waiterOrders = $waiter->orders()->whereBetween('created_at', [$data['from'], $data['to']]);

        return responder()->success($waiterOrders, OrderTransformer::class)->respond(Response::HTTP_OK);
    }
}
