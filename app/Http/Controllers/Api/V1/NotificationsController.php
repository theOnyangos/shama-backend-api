<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // This function sends verification code to user's email address
    public function sendPasswordVerificationCode($userEmail): JsonResponse
    {
        return $this->notificationService->sendVerificationCode($userEmail);
    }

    // This function checks the verification code
    public function checkVerificationCode(Request $request, $userEmail): JsonResponse
    {
        return $this->notificationService->checkVerificationCode($request, $userEmail);
    }

    // This function updates new user password in the database
    public function updateUserPassword(Request $request, $userEmail): JsonResponse
    {
        return $this->notificationService->updateNewUserPassword($request, $userEmail);
    }
}
