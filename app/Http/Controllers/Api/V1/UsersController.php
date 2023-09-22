<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\Api\V1\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psy\Util\Json;

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

    public function approveUser(Request $request, $userId): JsonResponse
    {
        return $this->userService->approveUserAccount($request, $userId);
    }

    public function updateUserDetails(Request $request, $userId): JsonResponse
    {
        return $this->userService->updateUser($request, $userId);
    }

    public function suspendAccount(Request $request, $userId): JsonResponse
    {
        return $this->userService->suspendUsersAccount($request, $userId);
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

    public function getUsersWithDetails(Request $request): JsonResponse
    {
        return $this->userService->getAllUsersWithDetails($request);
    }

    public function retrieveAccount($userId): JsonResponse
    {
        return $this->userService->retrieveUserAccount($userId);
    }

    // This function gets unverified users with their data
    public function getUnverifiedUsersWithDetails(Request $request): JsonResponse
    {
        $isApproved = 0;
        return $this->userService->getUnverifiedUsers($request, $isApproved);
    }

    // This method gets only players data
    public function getPlayersData(Request $request): JsonResponse
    {
        $isPlayer = 'player';
        return $this->userService->getPlayersOnly($request, $isPlayer);
    }

    public function getMaleAndFemaleCount(Request $request): JsonResponse
    {
        return $this->userService->getMaleFemaleCount($request);
    }

    // This function gets coaches only with their data
    public function getCoachesData(Request $request): JsonResponse
    {
        $isCoach = 'coach';
        return $this->userService->getCoachData($request, $isCoach);
    }

    public function getCoachesAndPlayersData(Request $request): JsonResponse
    {
        return $this->userService->getPlayersAndCoaches($request);
    }

    // This method updates the users account details
    public function updateUserAccount(Request $request, $userId): JsonResponse
    {
        return $this->userService->updateUserAccountDetails($request, $userId);
    }

    // This method updates the user password
    public function updateAccountPassword(Request $request, $userId): JsonResponse
    {
        return $this->userService->updateAccountPassword($request, $userId);
    }

    // This method uploads users profile image
    public function uploadUserProfileImage(Request $request, $userId): JsonResponse
    {
        return $this->userService->uploadUserImage($request, $userId);
    }

    // This method gets updated user information
    public function getUpdatedUserInformation(Request $request, $userId): JsonResponse
    {
        return $this->userService->getNewUserInformation($request, $userId);
    }

    // Get loggedIn user count details
    public function getLoggedInUsersCount(Request $request, $teamId): JsonResponse
    {
        return $this->userService->getLoggedInUsersCount($request, $teamId);
    }
}
