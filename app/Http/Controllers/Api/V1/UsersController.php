<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    protected UserService $userService;

    // The Constructor function
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getUsers(Request $request): JsonResponse
    {
        return $this->userService->getAllSystemUsers($request);
    }

    public function grantUserPermission($userId, $permissionId): JsonResponse
    {
        return $this->userService->addPermissionToUser($userId, $permissionId);
    }

    public function removeUserPermission($userId, $permissionId): JsonResponse
    {
        return $this->userService->removePermissionFromUser($userId, $permissionId);
    }

    public function createNewPermission(Request $request): JsonResponse
    {
        return $this->userService->createNewPermission($request);
    }

    public function approveUser($userId): JsonResponse
    {
        return $this->userService->approveUserAccount($userId);
    }

    public function updateUserDetails(Request $request, $userId): JsonResponse
    {
        return $this->userService->updateUser($request, $userId);
    }

    public function cancelApproval(Request $request, $userId): JsonResponse
    {
        return $this->userService->cancelApprovalRequest($request, $userId);
    }

    public function deleteAccount(Request $request, $userId): JsonResponse
    {
        return $this->userService->deleteAccountRequest($request, $userId);
    }

    public function getAllPermissions(Request $request): JsonResponse
    {
        return $this->userService->getAllUserPermissions($request);
    }

    public function getSingleUserDetails($userId): JsonResponse
    {
        return $this->userService->getSingleUserData($userId);
    }

    public function getUsersWithDetails(): JsonResponse
    {
        return $this->userService->getAllUsersWithDetails();
    }
}
