<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected TeamService $teamService;

    // The constructor function
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function createNewTeam(Request $request)
    {
        $this->teamService->createNewTeam($request);
    }
}
