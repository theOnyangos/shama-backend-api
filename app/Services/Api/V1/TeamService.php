<?php

namespace App\Services\Api\V1;

use App\Http\Requests\CreateTeamRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Models\TeamLocation;
use App\Models\TeamLocationUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeamService
{
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_SERVER = 500;

    public function createTeam($request, $adminId): JsonResponse
    {
        $message = "";
        $token = null;
        try {
            $validator = Validator::make(
                $request->all(),
                UserResource::validateNewTeamFields(),
                UserResource::customValidationMessages());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Handle image upload and store the file
            $fullPath = "";
            if ($request->hasFile('team_image')) {
                $uploadedFile = $request->file('team_image');
                $filename = "shama_".$request->team_name."_team_profile_". time() . '.' . $uploadedFile->getClientOriginalExtension();
                $filePath = 'assets/team_images/' . $filename; // Relative path within the public folder
                $fullPath = url($filePath); // Full path including the 'public' folder

                // Move and store the uploaded file
                $uploadedFile->move(public_path('assets/team_images'), $filename);

                // You can save the $filePath in your database or return it in the response
                $message = 'File uploaded successfully to: ' . $fullPath;
            } else {
                $message = 'No file was uploaded.';
            }

            // Store data in the database.
            $newTeamData = new Team();
            $newTeamData['team_name'] = $request->team_name;
            $newTeamData['team_image'] = $fullPath;
            $newTeamData['team_location'] = $request->team_location;
            $newTeamData['description'] = $request->description;
            $newTeamData['admin_id'] = $adminId;
            $newTeamData['coach_id'] = $adminId;

            if ($newTeamData->save()) {
                // Saved team ID
                $teamId = $newTeamData->id;
                // Save players and coaches.
                static::saveTeamPlayersAndCoaches($request, $teamId);
                // Return success message.
                $message = 'Congratulations! ' . $request->team_name . ', created successfully.';
            }

            return ApiResource::successResponse($newTeamData, $message, $token, self::STATUS_CODE_SUCCESS_CREATE);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    private static function saveTeamPlayersAndCoaches($request, $teamId): void
    {
        // Check if 'coaches' and 'players' data exists in the request and can be decoded.
        if ($request->has('coaches') && $request->has('players')) {
            $arrayCoachesData = json_decode($request->input('coaches'), true);
            $arrayPlayersData = json_decode($request->input('players'), true);

            if (is_array($arrayCoachesData)) {
                foreach ($arrayCoachesData as $coach) {
                    TeamLocationUser::create(['team_id' => $teamId, 'user_id' => $coach, 'role' => 'coach']);
                }
            }

            if (is_array($arrayPlayersData)) {
                foreach ($arrayPlayersData as $player) {
                    TeamLocationUser::create(['team_id' => $teamId, 'user_id' => $player, 'role' => 'player']);
                }
            }
        }
    }


    // This method add player to team
    public static function addPlayerToTeam($adminId, $teamId): JsonResponse
    {
        try {
            // Get user
            $user = User::with('team')->where('id', $adminId)->first();

            // Check if user is already added to team
            if ($user->team_id !== null) {
                $message = 'User is a player on Team: ' . $user->team->team_name . ' you might need to remove them from the other team first.';
                return ApiResource::validationErrorResponse('Validation Error!', $message, self::STATUS_CODE_ERROR);
            }

            // Update user table
            $updateData = ['team_id' => $teamId];
            User::where('id', $adminId)->update($updateData);

            // Return response
            $message = 'Congratulations!, ' . $user->first_name . ' ' . $user->last_name . ' added to team ' . $user->team->team_name . ' successfully.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS_CREATE);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method adds multiple players to team
    public static function addMultiplePlayersToTeam($request, $teamId): JsonResponse
    {
        try {
            $playerIds = $request->input('player_ids');

            // Get users by their IDs
            $players = User::whereIn('id', $playerIds)->get();

            // Check if any of the users are already added to a team
            $alreadyAddedUsers = $players->filter(function ($player) {
                return $player->team_id !== null;
            });

            if ($alreadyAddedUsers->count() > 0) {
                $usernames = $alreadyAddedUsers->map(function ($user) {
                    return $user->first_name . ' ' . $user->last_name;
                })->implode(', ');

                $message = 'The following users are already players on another team: ' . $usernames . '. You might need to remove them from their current teams first.';
                return ApiResource::validationErrorResponse('Validation Error!', $message, self::STATUS_CODE_ERROR);
            }

            // Update user table for each player
            foreach ($players as $player) {
                $updateData = ['team_id' => $teamId];
                $player->update($updateData);
            }

            // Get the team name using the team ID
            $team = Team::findOrFail($teamId);

            // Return response
            $message = 'Congratulations! ' . $players->count() . ' Players added to Team: ' . $team->team_name . ' successfully.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS_CREATE);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }

    }

    // This function updates the team account details
    public static function updateTeamAccountDetails($request, $teamId): JsonResponse
    {
        try {
            // Find the team by ID
            $team = Team::find($teamId);

            // Check if the team exists
            if (!$team) {
                $message = 'Team not found.';
                return ApiResource::validationErrorResponse('Validation Error!', $message, self::STATUS_CODE_ERROR);
            }

            // Validate the request data
            $validator = Validator::make(
                $request->all(),
                UserResource::validateNewTeamFields(), // Assuming you have a separate validation method for update fields
                UserResource::customValidationMessages()
            );

            // Return error message if validation fails
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Handle image upload and update the file if provided
            if ($request->hasFile('team_image')) {
                $filename = time() . '.' . $request->team_image->extension();
                $request->team_image->move(public_path('assets/team_images'), $filename);
                $apartmentImage = asset('team_images/' . $filename);
                $team->team_image = $apartmentImage;
            }

            // Update team data in the database
            $team->team_name = $request->team_name;
            $team->description = $request->description;
            $team->coach_id = $request->coach_id;
            $team->save();

            // Return response
            $message = 'Team details updated successfully.';
            $token = null;
            return ApiResource::successResponse($team, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // Soft delete team account
    public static function softDeleteTeamAccount($teamId): JsonResponse
    {
        try {
            $user = User::where('id', $teamId)->first();

            if ($user && $user->soft_delete === 1) {
                $message = 'This account is already deleted.';
                return ApiResource::validationErrorResponse('Validation error!!', $message, self::STATUS_CODE_FORBIDDEN);
            }

            // Delete user account
            $deleteData = ['soft_delete' => 1];
            User::where('id', $teamId)->update($deleteData);

            // Return response
            $message = 'Account Deleted successfully.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // Permanent delete team account
    public static function permanentDeleteTeamAccount($teamId): JsonResponse
    {
        try {

            // Return response
            $message = 'Account Permanently Deleted.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets a single team account details
    public static function getSingleTeamAccountDetails($teamId): JsonResponse
    {
        try {

            // Return response
            $message = 'Team Details Fetched successfully.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets all team members with their details
    public static function getTeamPlayersWithDetails($teamId): JsonResponse
    {
        try {

            // Return response
            $message = 'Team Members Fetched successfully.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets all team locations with data.
    public static function getAllTeamLocations($request): JsonResponse
    {
        try {
            // Retrieve all teams with their coaches, players, and player count
            $teams = Team::with(['members.user' => function ($query) {
                $query->where('user_type', 'coach');
            }, 'members.user' => function ($query) {
                $query->where('user_type', 'player');
            }])
                ->withCount(['members as player_count' => function ($query) {
                    $query->where('role', 'player');
                }])
                ->where('soft_delete', 0)
                ->get();

            // Return response
            $message = 'All team data Fetched successfully.';
            $token = null;
            return ApiResource::successResponse($teams, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets all players for a specific team
    public static function getAllTeamPlayers($request, $teamId): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            if ($teamId !== null) {
                $players = User::with('teamLocationUsers', 'roles')
                    ->where('user_type', 'player') // Use the actual column name for user_type
                    ->whereHas('teamLocationUsers', function ($query) use ($teamId) {
                        $query->where('team_id', $teamId);
                    })
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage, ['*'], 'page', $page);
            }

            // Return response
            $message = 'All players for team '. (new TeamService)->getTeamName($teamId).' fetched successfully.';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets all client graduated players
    public static function getClientGraduatedPlayers($request, $teamId): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            if ($teamId !== null) {
                $players = User::with('teamLocationUsers')
                    ->where('user_type', 'player')
                    ->where('is_graduated', 1)
                    ->whereHas('teamLocationUsers', function ($query) use ($teamId) {
                        $query->where('team_id', $teamId);
                    })
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage, ['*'], 'page', $page);
            }

            // Return response
            $message = 'All graduated players for team '. (new TeamService)->getTeamName($teamId).' fetched successfully.';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets all team coaches
    public static function getTeamCoaches($request, $teamId): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            if ($teamId !== null) {
                $coaches = User::with('teamLocationUsers', 'roles')
                    ->where('user_type', 'coach')
                    ->whereHas('teamLocationUsers', function ($query) use ($teamId) {
                        $query->where('team_id', $teamId);
                    })
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage, ['*'], 'page', $page);

                // Return response
                $message = 'All players for team '. (new TeamService)->getTeamName($teamId).' fetched successfully.';
                $token = null;
                return ApiResource::successResponse($coaches, $message, $token, self::STATUS_CODE_SUCCESS);
            }
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    private function getTeamName($teamId): string
    {
        $team = Team::where("id", $teamId)->first();
        return $team->team_name;
    }

    // This method gets all players that have not graduated
    public static function getUnGraduatedPlayers($request, $teamId): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 20; // Number of items per page

            if ($teamId === null) {
                $message = 'Team players not found.';
                return ApiResource::validationErrorResponse('Validation Error!', $message, self::STATUS_CODE_ERROR);
            }

            $players = User::select('id', 'first_name', 'last_name') // Select the desired columns
                ->with('teamLocationUsers')
                ->where('user_type', 'player') // Use the actual column name for user_type
                ->where('is_graduated', 0)
                ->whereHas('teamLocationUsers', function ($query) use ($teamId) {
                    $query->where('team_id', $teamId);
                })
                ->orderBy('id', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            // Return response
            $message = 'All players for team '. (new TeamService)->getTeamName($teamId).' fetched successfully.';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets all graduated and un-graduated players for client
    public static function getAllGraduatedAndNotGraduatedPlayers($request, $teamId): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 50; // Number of items per page

            if ($teamId === null) {
                $message = 'Team players not found.';
                return ApiResource::validationErrorResponse('Validation Error!', $message, self::STATUS_CODE_ERROR);
            }

            $team = Team::findOrFail($teamId);

            // Get an array of user IDs for the players in the team
            $userIds = $team->members->pluck('user_id')->toArray();

            // Retrieve the user records for the user IDs
            $players = User::whereIn('id', $userIds)->paginate($perPage, ['*'], 'page', $page);

            // Return response
            $message = 'All players for team '. (new TeamService)->getTeamName($teamId).' fetched successfully.';
            $token = null;
            return ApiResource::successResponse($players, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getTeamNamesIds($request): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 20; // Number of items per page

            $teams = Team::select('id', 'team_name')
                ->orderBy('id', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            // Return response
            $message = 'All teams fetched successfully.';
            $token = null;
            return ApiResource::successResponse($teams, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // Search players for client algorithm
    public static function searchForClientPlayers($request, $teamId): JsonResponse
    {
        try {
            $queryParam = $request->input("search");

            // Get an array of user IDs for the players in the team
            $team = Team::findOrFail($teamId);
            $userIds = $team->members->pluck('user_id')->toArray();

            $results = User::whereIn('id', $userIds)
                ->where(function ($query) use ($queryParam) {
                    $query->where('first_name', 'like', "%$queryParam%")
                        ->orWhere('last_name', 'like', "%$queryParam%")
                        ->orWhere('phone', 'like', "%$queryParam%")
                        ->orWhere('email', 'like', "%$queryParam%")
                        ->orWhere('member_id', 'like', "%$queryParam%");
                })
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
