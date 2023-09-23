<?php

namespace App\Services\Api\V1;

use Illuminate\Http\JsonResponse;

class HomeService
{
    public function saveAccountClosureReason($request): JsonResponse
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you for your feedback and for being part of the team.'
        ]);
    }
}
