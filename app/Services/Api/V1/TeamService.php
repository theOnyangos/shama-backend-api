<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
            if ($request->hasFile('team_image')) {
                $filename = time() . '.' . $request->team_image->extension();
                $request->team_image->move(public_path('assets/team_images'), $filename);
                $apartmentImage = asset('team_images/' . $filename);
                $newTeamData['team_image'] = $apartmentImage;
            }

            // Store data in the database.
            $newTeamData = new Team();
            $newTeamData['team_name'] = $request->team_name;
            $newTeamData['team_image'] = $request->team_image;
            $newTeamData['description'] = $request->description;
            $newTeamData['admin_id'] = $adminId;
            $newTeamData['coach_id'] = $request->coach_id;
            $newTeamData->save();

            // Return response
            $message = 'Congratulations! ' . $request->team_name . ', created successfully.';
            $token = null;
            return ApiResource::successResponse($newTeamData, $message, $token, self::STATUS_CODE_SUCCESS_CREATE);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
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
}
