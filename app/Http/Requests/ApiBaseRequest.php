<?php

namespace App\Http\Requests;

use App\Exceptions\HttpApiValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class ApiBaseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    abstract public function authorize();

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpApiValidationException('validation error', 'invalid data provided', $errors);
    }

    protected function checkCreateCategoryPermission($permissionName)
    {
        $userPermissions = auth('api')->user()->getPermissionsViaRoles();
        if (! $userPermissions->contains('name', $permissionName)) {
            throw new AccessDeniedHttpException('You do not have permission to '.$permissionName);
        }

        return true;
    }
}
