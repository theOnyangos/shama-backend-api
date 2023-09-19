<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CoachService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    // This method gets all coaches
    public static function getCoaches($request): JsonResponse
    {
        try {
            // Get the new players
            $coaches = User::where('user_type', 'coach')
                ->get();

            $message = 'All coaches fetched successfully';
            $token = null;
            return ApiResource::successResponse($coaches, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
