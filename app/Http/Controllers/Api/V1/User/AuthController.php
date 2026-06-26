<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Models\User;
use App\Repositories\User\UserRepository;
use App\Service\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private AuthService $authService
    ) {
        $this->userRepository = $userRepository;
        $this->authService = $authService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        Log::info('User just Logged In ', ['Username' => $credentials['username'], 'with Ip :' => request()->ip()]);

        $data = $this->authService->login($credentials,request()->ip() );

        if (! $data) {
            return responder()->error('credential_error', 'البيانات المدخلة غير صحيحة')->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success($data)->respond(Response::HTTP_OK);
    }

    public function logout(): JsonResponse
    {
        $logout = $this->authService->logout();
        if (! $logout) {
            return responder()->error('غير مصرح')->respond(Response::HTTP_UNAUTHORIZED);
        }

        return responder()->success(['message' => 'تم تسجيل الخروج'])->respond();
    }

    public function adminLogin($id)
    {
        $user = User::find($id);

        $data = $this->authService->adminLogin($user);

        if (! $data) {
            return responder()->error('credential_error', 'البيانات المدخلة غير صحيحة')->respond(Response::HTTP_BAD_REQUEST);
        }

        return responder()->success($data)->respond(Response::HTTP_OK);
    }
}
