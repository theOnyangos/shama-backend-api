<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ActivityHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Document;
use App\Models\User;
use App\Services\Api\V1\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_SERVER = 500;

    // Status codes
//    const STATUS_CODE_SUCCESS = 204;

    // Protected constructor classes
    protected AuthenticationService $authenticationService;

    // This is the constructor function
    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    // This method creates a new user registration
    public function createNewRegistration(Request $request): JsonResponse
    {
        return $this->authenticationService->registerNewUsers($request);
    }

    // This function creates a new staff member
    public function createNewStaffRegistration(Request $request): JsonResponse
    {
        return $this->authenticationService->registerNewStaff($request);
    }

    // This function logs in a user
    public function login(Request $request): JsonResponse
    {
        return $this->authenticationService->loginUser($request);
    }

    // This function logs user out of the system
    public function logout(): JsonResponse
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();
        return ApiResource::successResponse([], 'Logout successful', null, self::STATUS_CODE_SUCCESS);
    }

    // This function gets the team name
    public function getTeamName($userId): string|array
    {
        return $this->authenticationService->getTeamNameByUserId($userId);
    }
}
