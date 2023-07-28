<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    public static function getAllSystemUsers($request): JsonResponse
    {
        try {
            $users = User::all();

            $message = 'All users fetched successfully';
            $token = null;
            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getAllUserPermissions($request): JsonResponse
    {
        try {
            $roles = Role::all();

            $message = 'All permissions fetched successfully';
            $token = null;
            return ApiResource::successResponse($roles, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function addPermissionToUser($userId, $permissionId): JsonResponse
    {
        try {
            // Get user
            $user = User::where('id', $userId)->with('roles')->first();

            // Check permission is already assigned
            foreach ($user->roles as $role) {
                if ($role->name === $permissionId) {
                    $message = 'Permission already set for this user.';
                    return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
                }
            }

            // Get permission
            $teamRole = Role::where('name', $permissionId)->first();
            if ($teamRole) {
                $user->assignRole($teamRole);
            }

            $message = 'Permission added successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function removePermissionFromUser($userId, $permissionId): JsonResponse
    {
        try {
            // Get user
            $user = User::where('id', $userId)->with('roles')->first();

            // Check if permission is assigned to the user
            $hasPermission = false;
            foreach ($user->roles as $role) {
                if ($role->name === $permissionId) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                $message = 'Permission is not assigned to this user.';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            // Get role
            $teamRole = Role::where('name', $permissionId)->first();
            if ($teamRole) {
                $user->removeRole($teamRole);
            }

            $message = 'Permission removed successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }


    public static function createNewPermission($request): JsonResponse
    {
        $message = 'Permission created successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function approveUserAccount($request, $userId): JsonResponse
    {
        $message = 'User account approved successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function editUserDetails($request, $userId): JsonResponse
    {
        $message = 'User details updated successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function cancelApprovalRequest($request, $userId): JsonResponse
    {
        $message = 'Account suspended successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function deleteAccountRequest($request, $userId): JsonResponse
    {
        $message = 'Account deleted successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function getSingleUserData($userId): JsonResponse
    {
        try {
            $userDetails = User::with('addressDetails', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')->where('id', $userId)->first();

            $message = 'User details retried successfully';
            $token = null;
            return ApiResource::successResponse($userDetails, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getAllUsersWithDetails(): JsonResponse
    {
        try {
            $users = User::with('addressDetails', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')->get();

            $message = 'All users with details retried successfully';
            $token = null;
            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
