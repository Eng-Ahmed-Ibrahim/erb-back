<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ForgetPasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Repositories\User\UserRepository;
use App\Transformers\User\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function __construct(private UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

    }

    public function index()
    {
        $user = auth()->user('api');

        return responder()->success($user, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    public function update(UpdateUserRequest $request)
    {
        $data = $request->validated();
        $user = $this->userRepository->updateProfile($data);

        return responder()->success($user, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    public function updateDepartment(Request $request)
    {
        $data = $request->validate(['department_id' => 'nullable']);
        $user = $this->userRepository->updateProfile($data);

        return responder()->success($user, UserTransformer::class)->respond(Response::HTTP_OK);
    }

    public function changePassword(ForgetPasswordRequest $request)
    {
        $data = $request->validated();
        $this->userRepository->updatePassword($data);

        return responder()->success(['message' => 'تم تغير كلمة المرور'])->respond(Response::HTTP_OK);
    }
}
