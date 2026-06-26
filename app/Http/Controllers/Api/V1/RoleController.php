<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Role\RoleRepository;
use App\Transformers\Permission\PermissionTransformer;
use App\Transformers\Role\AbstractRoleTransformer;
use App\Transformers\Role\RoleTransformer;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function __construct(private RoleRepository $roleRepository,
        private PermissionRepository $permissionRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;

    }

    public function index()
    {
        $roles = $this->roleRepository->all();

        return responder()->success($roles, AbstractRoleTransformer::class)->respond(Response::HTTP_OK);
    }

    public function permissions()
    {
        $permissions = $this->permissionRepository->all();

        return responder()->success($permissions, PermissionTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show($roleId)
    {
        $role = $this->roleRepository->adminShow($roleId);

        return responder()->success($role, RoleTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();
        $role = $this->roleRepository->adminCreate($data);

        return responder()->success($role, RoleTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, $roleId)
    {
        $data = $request->validated();
        $role = $this->roleRepository->adminShow($roleId);
        $role = $this->roleRepository->adminUpdate($role, $data);

        return responder()->success($role, RoleTransformer::class)->respond(Response::HTTP_OK);
    }
}
