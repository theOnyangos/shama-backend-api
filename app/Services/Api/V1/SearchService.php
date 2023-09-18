<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class SearchService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    // This function queries the database for search results
    public static function searchUsers($request): JsonResponse
    {
        try {
            $queryParam = $request->input("search");
            $results = User::where('first_name', 'like', "%$queryParam%")
                ->orWhere('last_name', 'like', "%$queryParam%")
                ->orWhere('phone', 'like', "%$queryParam%")
                ->orWhere('email', 'like', "%$queryParam%")
                ->orWhere('member_id', 'like', "%$queryParam%")
                ->get();

            $message = 'Search results for: '.$queryParam." found.";
            $token = null;
            return ApiResource::successResponse($results, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
