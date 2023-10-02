<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTeamRequest;
use App\Http\Resources\ApiResource;
use App\Services\Api\V1\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected TeamService $teamService;

    // The constructor function
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    // This function create a new team.
    public function createNewTeam(Request $request, $adminId): JsonResponse
    {
        return $this->teamService->createTeam($request, $adminId);
    }

    // This function adds a member to a team
    public function addNewTeamMember($adminId, $teamId): JsonResponse
    {
        return $this->teamService->addPlayerToTeam($adminId, $teamId);
    }

    // This function add multiple users to team
    public function addMultipleTeamMember(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->addMultiplePlayersToTeam($request, $teamId);
    }

    // This function updates the team account details
    public function updateTeamAccountDetails(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->updateTeamAccountDetails($request, $teamId);
    }

    // Delete team account (soft-delete)
    public function deleteTeamAccountDetails($teamId): JsonResponse
    {
        return $this->teamService->softDeleteTeamAccount($teamId);
    }

    // This function permanent deletes the team account and remove team.
    public function permanentDeleteTeamAccountDetails($teamId): JsonResponse
    {
        return $this->teamService->permanentDeleteTeamAccount($teamId);
    }

    // Get single account details
    public function getAccountDetails($teamId): JsonResponse
    {
        return $this->teamService->getSingleTeamAccountDetails($teamId);
    }

    // Public function get team players (Members of certain team)
    public function getTeamPlayers($teamId): JsonResponse
    {
        return $this->teamService->getTeamPlayersWithDetails($teamId);
    }

    // This method gets all team location Data
    public function getAllTeamLocationData(Request $request): JsonResponse
    {
        return $this->teamService->getAllTeamLocations($request);
    }

    // This method gets all team-players
    public function getAllTeamPlayers(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->getAllTeamPlayers($request, $teamId);
    }

    // This method gets all team coaches for a team-group
    public function getAllTeamCoaches(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->getTeamCoaches($request, $teamId);
    }

    // This function gets team players not graduated
    public function getPlayersNotGraduated(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->getUnGraduatedPlayers($request, $teamId);
    }

    // This method gets all team names with ids
    public function getAllTeamNamesWithIds(Request $request): JsonResponse
    {
        return $this->teamService->getTeamNamesIds($request);
    }

    // This method gets graduated team players for specified clients
    public function getAllClientGraduatedPlayers(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->getClientGraduatedPlayers($request, $teamId);
    }

    // This function gets all players both graduated and not graduated from the database
    public function getGraduatedAndNotGraduatedPlayers(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->getAllGraduatedAndNotGraduatedPlayers($request, $teamId);
    }

    // This function searches in clients for players
    public function searchClientPlayers(Request $request, $teamId): JsonResponse
    {
        return $this->teamService->searchForClientPlayers($request, $teamId);
    }
}
