<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\StatisticalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticalDataController extends Controller
{
    private StatisticalService $statisticalService;

    public function __construct(StatisticalService $statisticalService)
    {
        $this->statisticalService = $statisticalService;
    }

    public function getStatisticalData(Request $request): JsonResponse
    {
        return $this->statisticalService->getStatisticalAppData($request);
    }
}
