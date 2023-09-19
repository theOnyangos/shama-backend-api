<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\PlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Js;
use Psy\Util\Json;

class PlayersController extends Controller
{
    private PlayerService $playerService;

    // This is the constructor function
    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
    }

    // This function gets all new players from the database
    public function getAllNewPlayers(Request $request): JsonResponse
    {
        return $this->playerService->getNewPlayers($request);
    }

    // This function gets all players from the database
    public function getAllPlayers(Request $request): JsonResponse
    {
        return $this->playerService->getAllPlayers($request);
    }

    // This method gets all graduated players
    public function getAllGraduatedPlayers(Request $request): JsonResponse
    {
        return $this->playerService->getGraduatedPlayers($request);
    }
}
