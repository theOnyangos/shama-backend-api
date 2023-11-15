<?php

namespace App\Services\Api\V1;

use App\Helpers\ActivityHelper;
use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\EducationDetail;
use App\Models\MedicalDetail;
use App\Models\Team;
use App\Models\TeamLocationUser;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserOtherDetail;
use App\Notifications\AccountCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Js;
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
                ->with('roles')
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

    // This method registers new players and adds them to team
    public static function registerNewPlayerAndAddToTeam($request, $coachId): JsonResponse
    {
        try {
            // Run Validation
            $validator = Validator::make(
                $request->all(),
                UserResource::teamValidationFields(),
                UserResource::customValidationMessages());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'There is information missing in your form fields. Check and submit all the required data.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Check if a user with the same email already exists
            $is_existing_user = User::where('email', $request->email)->first();
            if ($is_existing_user) {
                $message = 'A user with the same email already exists.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            $password = static::generatePassword(8);

            // Store users data
            $personalDetails = new User();
            $personalDetails['member_id'] = static::generateSequentialId();
            $personalDetails['first_name'] = $request->first_name;
            $personalDetails['last_name'] = $request->last_name;
            $personalDetails['email'] = $request->email;
            $personalDetails['phone'] = $request->phone;
            $personalDetails['age'] = $request->age;
            $personalDetails['user_type'] = "player";
            $personalDetails['password'] = Hash::make($password);
            $personalDetails->save();

            // Get the created ID
            $savedUserId = $personalDetails->id;
            // Store Medical Details
            static::storeMedicalDetails($request, $savedUserId);
            // Store Address Details
            static::storeAddressDetails($request, $savedUserId);
            // Store Education Details
            static::storeEducationDetails($request, $savedUserId);
            // Store User Other Details
            static::storeOtherDetails($request, $savedUserId);

            // Assign the default role "team" to the user
            $teamRole = Role::where('name', 'team')->first();
            if ($teamRole) {
                $personalDetails->assignRole($teamRole);
            }

            // Add user to team
            static::addUserToTeam($coachId, $savedUserId);

            // Send notification to users email
            $personalDetails->notify(new AccountCreated($request->first_name." ".$request->last_name, $request->email, $password));

            $userName = ActivityHelper::getUserName($request->user_id ?? 1);
            ActivityHelper::logActivity($request->user_id ?? 1, $userName." Registered a new team player: ".$request->first_name." ".$request->last_name);

            $message = 'New player registered and added to team.';
            $token = null;
            return ApiResource::successResponse($personalDetails, $message, $token, self::STATUS_CODE_SUCCESS_CREATE);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function generatePassword($length = 12): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_';

        $password = '';
        $characterCount = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[mt_rand(0, $characterCount)];
        }

        return $password;
    }

    // This method adds user to team after registration
    private static function addUserToTeam($coachId, $userId): void
    {
        // Check if user has a team then get team ID
        $teamResult = static::getTeamNameByUserId($coachId);
        if(!empty($teamResult)) {
            // Add user to team
            $teamId = $teamResult['team_id'];
            // Add user in the database
            TeamLocationUser::create(['team_id' => $teamId, 'user_id' => $userId, 'role' => 'player']);
        } else {
            TeamLocationUser::create(['team_id' => $coachId, 'user_id' => $userId, 'role' => 'player']);
        }
    }

    // This function stores medical details
    private static function storeMedicalDetails($request, $userId): void
    {
        // Initialize the medical model
        $medicalDetails = new MedicalDetail();
        $medicalDetails['user_id'] = $userId;
        $medicalDetails['injuries'] = $request->injuries;
        $medicalDetails['allergies'] = $request->allergies;
        $medicalDetails['medical_conditions'] = $request->medical_conditions;
        $medicalDetails['medications'] = $request->medications;
        $medicalDetails['gender'] = $request->gender;
        $medicalDetails->save();
    }

    // This function stores address details
    private static function storeAddressDetails($request, $userId): void
    {
        // Initialize the UserAddressModel
        $addressDetails = new UserAddress();
        $addressDetails['user_id'] = $userId;
        $addressDetails['address'] = $request->address;
        $addressDetails['city'] = $request->city;
        $addressDetails['county_id'] = $request->county_id;
        $addressDetails['region_id'] = $request->region_id;
        $addressDetails['street_id'] = $request->street_id;
        $addressDetails['coach_id'] = $request->coach_id;
        $addressDetails->save();
    }

    // This function stores education details
    private static function storeEducationDetails($request, $userId): void
    {
        // Initialize the UserEducationDetailsInput
        $educationDetails = new EducationDetail();
        $educationDetails['user_id'] = $userId;
        $educationDetails['school_level'] = $request->school_level;
        $educationDetails['school_address'] = $request->school_address;
        $educationDetails['school_city'] = $request->school_city;
        $educationDetails['school_phone'] = $request->school_phone;
        $educationDetails['school_email'] = $request->school_email;
        $educationDetails['school_grade'] = $request->school_grade;
        $educationDetails['school_counselor_name'] = $request->school_counselor_name;
        $educationDetails->save();
    }

    // This function stores other user details
    private static function storeOtherDetails($request, $userId): void
    {
        // Initialize UserOtherDetails
        $otherDetails = new UserOtherDetail();
        $otherDetails['user_id'] = $userId;
        $otherDetails['emergency_contact_name'] = $request->emergency_contact_name;
        $otherDetails['emergency_contact_phone'] = $request->emergency_contact_phone;
        $otherDetails['emergency_contact_email'] = $request->emergency_contact_email;
        $otherDetails['emergency_notes'] = $request->emergency_notes;
        $otherDetails->save();
    }

    // This function generates new IDs
    private static function generateSequentialId(): string
    {
        // Get the last record from the database table
        $lastRecord = User::latest('member_id')->first();

        // If there are no records yet, start with an initial ID
        $lastId = $lastRecord ? $lastRecord->member_id : 100000;

        // Compute the next ID by adding 1 to the last ID
        $nextId = $lastId + 1;

        // Return the next ID as a string with leading zeros
        return str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    private static function getTeamNameByUserId($userId): array
    {
        // Find the user by user ID
        $user = User::find($userId);

        if ($user) {
            // Assuming the user has a relationship with teamLocationUsers
            $teamLocationUsers = $user->teamLocationUsers;

            if ($teamLocationUsers->isNotEmpty()) {
                // You can loop through the teamLocationUsers if there are multiple
                // or simply get the first one if that's what you need
                $firstTeamLocationUser = $teamLocationUsers->first();

                // Assuming the teamLocationUser has a 'team_id' field
                $teamId = $firstTeamLocationUser->team_id;

                // Find the team by team ID
                $team = Team::find($teamId);

                return ['team_name' => $team->team_name, 'team_id' => $team->id];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    // This function updates single player details in the database
    public static function updatePlayerDetails($request, $userId): JsonResponse
    {
        try {
            // Run The update player form inputs
            $validator = Validator::make(
                $request->all(),
                UserResource::playerValidationFieldsUpdate(),
                UserResource::customValidationMessages());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'There is information missing in your form fields. Check and submit all the required data.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Check if user with the provided id exists
            $user = User::find($userId);
            if (!$user) {
                $message = "No user found for the provided ID";
                return ApiResource::errorResponse($message, self::STATUS_CODE_NOT_FOUND);
            }

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->age = $request->age;

            // Update Medical Details
            static::updateMedicalDetails($request, $user->id);
            // Update Address Details
            static::updateAddressDetails($request, $user->id);
            // Update Education Details
            static::updateEducationDetails($request, $user->id);
            // Update User Other Details
            static::updateOtherDetails($request, $user->id);

            $user->save();

            $message = 'All details for '.$user->first_name." ".$user->last_name.' updated successfully';
            $token = null;

            $userName = ActivityHelper::getUserName($userId);
            ActivityHelper::logActivity($userId, $userName." updated details for: ".$user->first_name." ".$user->last_name);

            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method updates players medical details
    private static function updateMedicalDetails($request, $userId): void
    {
        // Initialize the medical model
        $medicalDetails = [];
        $medicalDetails['injuries'] = $request->injuries;
        $medicalDetails['allergies'] = $request->allergies;
        $medicalDetails['medical_conditions'] = $request->medical_conditions;
        $medicalDetails['medications'] = $request->medications;
        $medicalDetails['gender'] = $request->gender;
        MedicalDetail::where('user_id', $userId)->update($medicalDetails);
    }

    // This method updates players medical details
    private static function updateAddressDetails($request, $userId): void
    {
        // Initialize the UserAddressModel
        $addressDetails = [];
        $addressDetails['address'] = $request->address;
        $addressDetails['city'] = $request->city;

        UserAddress::where('user_id', $userId)->update($addressDetails);
    }

    // This method updates player educational details
    private static function updateEducationDetails($request, $userId): void
    {
        // Initialize the UserEducationDetailsInput
        $educationDetails = [];
        $educationDetails['school_level'] = $request->school_level;
        $educationDetails['school_address'] = $request->school_address;
        $educationDetails['school_city'] = $request->school_city;
        $educationDetails['school_phone'] = $request->school_phone;
        $educationDetails['school_email'] = $request->school_email;
        $educationDetails['school_grade'] = $request->school_grade;
        $educationDetails['school_counselor_name'] = $request->school_counselor_name;

        EducationDetail::where('user_id', $userId)->update($educationDetails);
    }

    // This method updates players other details
    private static function updateOtherDetails($request, $userId): void
    {
        // Initialize UserOtherDetails
        $otherDetails = [];
        $otherDetails['emergency_contact_name'] = $request->emergency_contact_name;
        $otherDetails['emergency_contact_phone'] = $request->emergency_contact_phone;
        $otherDetails['emergency_contact_email'] = $request->emergency_contact_email;
        $otherDetails['emergency_notes'] = $request->emergency_notes;

        UserOtherDetail::where('user_id', $userId)->update($otherDetails);
    }
}
