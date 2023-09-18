<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    // This function creates a new role permission
    public function createNewRolePermission(Request $request): JsonResponse
    {
        return $this->permissionService->createNewRolePermission($request);
    }

    // This function gets all roles and permissions
    public function getRolesWithPermission(Request $request): JsonResponse
    {
        return $this->permissionService->getRolesWithPermission($request);
    }
}
