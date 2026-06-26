<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SearchUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Models\ModelHasModel;
use App\Models\User;
use App\Repositories\Role\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Transformers\Order\OrderTransformer;
use App\Transformers\User\AbstractUserTransformer;
use App\Transformers\User\UserTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function index(SearchUserRequest $request)
    {
        $data = $request->validated();

        if (count($data) > 0) {
            if (isset($data['role'])) {
                $role = $this->roleRepository->adminShow($data['role']);
                $usersHasRole = $role->users;
                unset($data['role']);
            }
            $users = $this->userRepository->getInterceptedByAttributes($data, 'created_at', 'desc');

            if (isset($usersHasRole)) {
                $users = $users->intersect($usersHasRole);
            }

            return responder()->success($this->userRepository->paginate($users), AbstractUserTransformer::class)->respond(Response::HTTP_OK);
        }

        $users = $this->userRepository->allPaginated();

        return responder()->success($users, AbstractUserTransformer::class)->respond(Response::HTTP_OK);
    }

    /**
     * Create a new user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userRepository->adminCreate($request->validated());

        return responder()->success($user, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    /**
     * Display the specified user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($user_id)
    {
        $user = $this->userRepository->find($user_id);

        return responder()->success($user, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    /**
     * Update the specified user role
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRole(UpdateUserRoleRequest $request, $user_id)
    {
        $data = $request->validated();
        $user = $this->userRepository->find($user_id);
        $role = $this->roleRepository->adminShow($data['role']);
        $user->syncRoles($role);

        return responder()->success(['message' => 'تم تعديل الدور بنجاح'])->respond(Response::HTTP_OK);
    }

    /**
     * Remove the specified user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($user_id)
    {
        $user = $this->userRepository->find($user_id);
        $this->userRepository->delete($user);

        return responder()->success(['message' => 'تم حذف المستخدم بنجاح'])->respond(Response::HTTP_OK);
    }

    public function all()
    {
        $users = $this->userRepository->all();

        return responder()->success($users, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(Request $request, $user_id)
    {
        $data = $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $user_id,
            'password' => $request->has('password') ? 'required|confirmed' : 'nullable',
            'department_id' => 'required|exists:departments,id',
            'reviewer' => 'nullable',
        ]);

        $user = User::findOrFail($user_id);
        if ($request->has('password')) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['reviewer']) && $data['reviewer']) {
            ModelHasModel::updateOrCreate([
                'source_model_id' => $user->id,
                'operation' => 'reviewed by',
            ], [
                'source_model' => 'App\Models\User',
                'target_model' => 'App\Models\User',
                'target_model_id' => $data['reviewer'],
            ]);
            unset($data['reviewer']);
        }

        $user->update($data);

        return responder()->success(['message' => 'User updated successfully'])->respond(Response::HTTP_OK);
    }

    public function updateDepartment(Request $request, $id)
    {
        $data = $request->validate(['department_id' => 'required']);
        $user = $this->userRepository->find($id);
        $user->update([
            'department_id' => $data['department_id'],
        ]);

        return responder()->success($user, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    public function orders(Request $request, $user_id)
    {
        $department = auth()->user()->department;

        $data = $request->validate([
            'from' => 'required',
            'to' => 'nullable',
            'department_id' => 'nullable',
        ]);
        if ($department->type != 'master') {
            $checkDate = $this->checkDate($data['from']);
            $to = Carbon::today();
            if (! $checkDate) {
                return responder()->error('Date_validation', 'لا يمكنك جلب تقرير لمده تزيد عن يوم واحد للخلف');
            }
        } else {
            $to = $data['to'] ? Carbon::parse($data['to']) : $to = Carbon::today();
        }

        $user = $this->userRepository->find($user_id);
        $orders = $user
            ->orders()
            ->where('created_at', '>=', $data['from'])
            ->where('created_at', '<=', $to->format('Y-m-d H:i:s'));

        if ($data['department_id']) {
            $orders->where('department_id', $data['department_id']);
        }

        $orders = $orders->get();

        $totals = [
            'total_visa' => $orders->where('payment_method', 'visa')->sum('total_price'),
            'total_cash' => $orders->where('payment_method', 'cash')->sum('total_price'),
            'total_post_paid' => $orders->where('payment_method', 'postpaid')->sum('total_price'),
            'total_hospitality' => $orders->where('payment_method', 'hospitality')->sum('total_price'),
            'total' => $orders->sum('total_price'),
        ];

        $formatedOrders = [];
        foreach ($orders as $order) {
            $formatedOrders[] = (new OrderTransformer)->transform($order);
        }

        $formatedOrders['totals'] = $totals;

        return responder()->success($formatedOrders)->respond(Response::HTTP_OK);
    }

    private function checkDate($from)
    {
        if (Carbon::parse($from)->isToday() || Carbon::parse($from)->isYesterday()) {
            return true;
        }

        return false;
    }

    /**
     * Get users with stocks role
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStockUsers()
    {
        $stockUsers = $this->roleRepository->adminShow(\App\Models\Role::STOCK_ROLE_ID)?->users;
        $supplyUsers = $this->roleRepository->adminShow(\App\Models\Role::SUPPLY_ROLE_ID)?->users;

        $users = $stockUsers->merge($supplyUsers);

        return responder()->success($users, UserTransformer::class)->respond(Response::HTTP_OK);
    }
}
