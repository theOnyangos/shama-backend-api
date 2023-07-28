<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
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
}
