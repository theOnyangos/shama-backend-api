<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\CoachService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachesController extends Controller
{
    private CoachService $coachService;

    // Constructor Function
    public function __construct(CoachService $coachService)
    {
        $this->coachService = $coachService;
    }

    // This method gets all the coaches from the database
    public function getAllCoaches(Request $request): JsonResponse
    {
        return $this->coachService->getCoaches($request);
    }
}
