<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Models\TeamLocation;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatisticalService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    public function getStatisticalAppData($request): JsonResponse
    {
        try {
            $statistics = [
                [
                    'title' => 'Total Users',
                    'count' => static::countTotalUsers(),
                ],
                [
                    'title' => 'Total Players',
                    'count' => static::countAllPlayers(),
                ],
                [
                    'title' => 'Total Coaches',
                    'count' => static::countAllCoaches(),
                ],
                [
                    'title' => 'Total Locations',
                    'count' => static::countLocations(),
                ],
                [
                    'title' => 'Social Workers',
                    'count' => static::countSocialWorkers(),
                ],
                [
                    'title' => 'Trainings',
                    'count' => static::countTrainings(),
                ],
            ];

            $message = 'Statistical data retrieved successfully.';
            $token = null;

            return ApiResource::successResponse($statistics, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    private static function countTotalUsers()
    {
        // Assuming you have a "users" table
        return User::count();
    }

    private static function countAllPlayers()
    {
        // Assuming you have a "users" table with a "user_type" column
        return User::where('user_type', 'player')->count();
    }

    private static function countAllCoaches()
    {
        // Assuming you have a "users" table with a "user_type" column
        return User::where('user_type', 'coach')->count();
    }

    private static function countLocations()
    {
        // Assuming you have a "locations" table
        return TeamLocation::count();
    }

    private static function countSocialWorkers()
    {
        // Assuming you have a "users" table with a "user_type" column
        return User::where('user_type', 'social_worker')->count();
    }

    private static function countTrainings()
    {
        // Assuming you have a "trainings" table
        return Training::count();
    }
}
