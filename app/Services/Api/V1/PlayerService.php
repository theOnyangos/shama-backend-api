<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class PlayerService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    // This function gets all new players from the database
    public static function getNewPlayers($request): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            // Get the new players
            $players = User::where('user_type', 'player')
                ->where('approved', 0)
                ->orderBy('id', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            $message = 'New players fetched successfully';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets all players
    public static function getAllPlayers($request): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            // Get the new players
            $players = User::where('user_type', 'player')
                ->orderBy('id', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            $message = 'All players fetched successfully';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets all graduated players from the database
    public static function getGraduatedPlayers($request): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            // Get the new players
            $players = User::where('user_type', 'player')
                ->where('is_graduated', 1)
                ->orderBy('id', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            $message = 'All graduated players fetched successfully';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
