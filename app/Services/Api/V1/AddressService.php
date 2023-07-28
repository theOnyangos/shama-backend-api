<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use Illuminate\Http\JsonResponse;

class AddressService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    public static function getKenyanCounties(): JsonResponse
    {
        $message = 'All counties fetched successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function getRegionsInCounties(): JsonResponse
    {
        $message = 'County regions fetched successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function getStreetsInRegion(): JsonResponse
    {
        $message = 'Region streets fetched successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }
}
