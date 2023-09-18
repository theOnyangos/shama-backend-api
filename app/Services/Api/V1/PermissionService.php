<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    public static function createNewRolePermission($request): JsonResponse
    {
        try {
            $validator = Validator::make(
                $request->all(),
                UserResource::validateRolePermission());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            $roleName = $request->role_name;
            $newPermissionName = $request->permission_name;
            $userRole = Role::where('name', $roleName)->first();

            // Create permission
            $newPermission = Permission::create(['name' => $newPermissionName, 'guard_name' => 'web']);
            $userRole->givePermissionTo($newPermission);

            $message = 'New permission Role Created successfully';
            $token = null;
            return ApiResource::successResponse($newPermission, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getRolesWithPermission($request): JsonResponse
    {
        try {
            $rolesWithPermissions = Role::with('permissions')->get();

            $message = 'All Roles and permissions fetched successfully';
            $token = null;
            return ApiResource::successResponse($rolesWithPermissions, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
