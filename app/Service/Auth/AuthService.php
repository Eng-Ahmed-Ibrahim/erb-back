<?php

namespace App\Service\Auth;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Role\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Service\NetworkTracking\Models\NetworkDevice;
use App\Transformers\User\UserTransformer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function login($credentials, $ip)
    {
        if (Auth::attempt($credentials)) {
            $user = Auth::user('api');
            $token = $user->createToken('authToken')->accessToken;
            //  fuck u, who trynna to fuck with this code.

            if ($user->isCashier()) {
                $query = NetworkDevice::where('ip_address', $ip)->whereNotNull('department_id');
                if ($query->exists()) {
                    $user->update([
                        'department_id' => $query?->first()?->department_id
                    ]);
                    $user->refresh();

                    if ($query?->first()?->department_id === '01kbckrxfmmp8s3t4hs2smb4bq') {
                        $user->syncRoles('activities-cashier');
                        Log::info('ff----122-----ffff');

                    }else {
                        Log::info('ff----------ffff');
                        $user->syncRoles('cashier');

                    }
                }
            }

            $formatedUser = UserTransformer::transform($user);
            $formatedUser['token'] = $token;

            return $formatedUser;
        }

        return false;
    }

    public function logout()
    {
        $user = Auth::user('api');
        if ($user) {
            $user->token()->revoke();

            return true;
        }

        return false;
    }

    public function adminLogin($user)
    {
        if ($user) {
            $token = $user->createToken('authToken')->accessToken;
            $formatedUser = UserTransformer::transform($user);
            $formatedUser['token'] = $token;

            return $formatedUser;
        }

        return false;
    }
}
