<?php

namespace App\Services\Api\V1;

use App\Helpers\ActivityHelper;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CoachesPlayersResource;
use App\Http\Resources\UserResource;
use App\Models\CloseAccountMessage;
use App\Models\EducationDetail;
use App\Models\MedicalDetail;
use App\Models\Team;
use App\Models\TeamLocationUser;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserOtherDetail;
use App\Notifications\AccountApproved;
use App\Notifications\AccountDeleted;
use App\Notifications\AccountSuspended;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Js;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    public static function getAllSystemUsers($request): JsonResponse
    {
        try {
            $users = User::all();

            $message = 'All users fetched successfully';
            $token = null;
            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getAllUserPermissions($request): JsonResponse
    {
        try {
            $roles = Role::all();

            $message = 'All permissions fetched successfully';
            $token = null;
            return ApiResource::successResponse($roles, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function addPermissionToUser($request): JsonResponse
    {
        try {
            $userId = $request->user_id;
            $permissionId = $request->role;

            // Get user
            $user = User::where('id', $userId)->with('roles')->first();

            // Check permission is already assigned
            foreach ($user->roles as $role) {
                if ($role->name === $permissionId) {
                    $message = 'Permission already set for this user.';
                    return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
                }
            }

            // Get permission
            $teamRole = Role::where('name', $permissionId)->first();
            if ($teamRole) {
                $user->assignRole($teamRole);
            }

            $message = 'Permission added successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function removePermissionFromUser($request): JsonResponse
    {
        try {
            $userId = $request->user_id;
            $permissionId = $request->role;

            // Get user
            $user = User::where('id', $userId)->with('roles')->first();

            // Check if permission is assigned to the user
            $hasPermission = false;
            foreach ($user->roles as $role) {
                if ($role->name === $permissionId) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                $message = 'Permission is not assigned to this user.';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            // Get role
            $teamRole = Role::where('name', $permissionId)->first();
            if ($teamRole) {
                $user->removeRole($teamRole);
            } else {
                $message = 'Role not found';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            $message = 'Permission removed successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function createNewPermission($request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            UserResource::validateRoleFields());

        // Return error message if one or more input fields are empty
        if ($validator->fails()) {
            $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
            return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
        }

        Role::create(['name' => $request->role_name, 'guard_name' => 'web']);

        $message = 'Permission created successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    // This function approves the users account
    public static function approveUserAccount($request, $userId): JsonResponse
    {
        try {
            // Check if user is already approved
            $user = User::where('id', $userId)->first();
            if ($user && $user->approved === 1) {
                $message = 'This account is already approved.';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            // Approve user account
            $approveData = ['approved' => 1];
            User::where('id', $userId)->update($approveData);
            $user = User::where('id', $userId)->first();

            // Send email to user that their account has been approved
            $user->notify(new AccountApproved($user->first_name.' '.$user->last_name, $user->member_id));
            // Create Notification

            $message = 'User account for '.$user->first_name.' '.$user->last_name.' has been approved successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function update the team's user details.

    /**
     * @param $request
     * @return JsonResponse
     */
    public static function extracted($request, $isApproved=null, $isPlayer=null): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            if ($isApproved !== null) {
                // Fetch paginated data
                $users = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                    ->orderBy('id', 'DESC')
                    ->where('approved', $isApproved)
                    ->paginate($perPage, ['*'], 'page', $page);

                $message = 'All approved users with details retrieved successfully';
            } elseif ($isPlayer !== null) {
                $users = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                    ->orderBy('id', 'DESC')
                    ->where('user_type', $isPlayer)
                    ->paginate($perPage, ['*'], 'page', $page);

                $message = 'All players retrieved successfully';
            } else {
                $users = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage, ['*'], 'page', $page);

                $message = 'All users with details retrieved successfully';
            }


            $token = null;
            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public function updateUser($request, $userId): JsonResponse
    {
        try {
            // Run Validation
            $validator = Validator::make(
                $request->all(),
                UserResource::teamValidationFieldsUpdate(),
                UserResource::customValidationMessages()
            );

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Find the user by the provided user ID
            $user = User::find($userId);

            // Check if the user exists
            if (!$user) {
                $message = 'User not found.';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            // Check if the email is being updated to an existing email
            if ($request->has('email') && $user->email !== $request->email) {
                $existingUser = User::where('email', $request->email)->first();
                if ($existingUser) {
                    $message = 'A user with the same email already exists.';
                    return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
                }
            }

            // Update user data
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            // Update Medical Details
            static::updateMedicalDetails($request, $userId);
            // Update Address Details
            static::updateAddressDetails($request, $userId);
            // Update Education Details
            static::updateEducationDetails($request, $userId);
            // Update User Other Details
            static::updateOtherDetails($request, $userId);

            // Return success response
            $message = 'User details updated successfully.';
            $token = null;

            $userName = ActivityHelper::getUserName($userId);
            ActivityHelper::logActivity($userId, $userName." updated: ". $user->first_name." ".$user->last_name."'s details");

            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);

        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function cancels user approval
    public static function suspendUsersAccount($request, $userId): JsonResponse
    {
        try {
            // Check if user is already approved
            $user = User::where('id', $userId)->first();
            if (!$user) return ApiResource::validationErrorResponse('Not Found!', 'User dose not exist in the system.', self::STATUS_CODE_NOT_FOUND);

            if ($user->is_suspended === 1) {
                $message = 'This account is already suspended.';
                return ApiResource::validationErrorResponse('Forbidden!', $message, self::STATUS_CODE_FORBIDDEN);
            }

            // Approve user account
            $suspendData = ['is_suspended' => 1];
            User::where('id', $userId)->update($suspendData);

            // Send notification to email
            $user->notify(new AccountSuspended($user->first_name." ".$user->lasr_name));

            $message = 'Account suspended successfully. Users of the account will not be able to sign in until the account is restored.';
            $token = null;
            return ApiResource::successResponse(User::where('id', $userId)->first(), $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function soft delete the users account
    public static function deleteAccountRequest($request, $userId): JsonResponse
    {
        try {
            $user = User::where('id', $userId)->first();

            if ($user && $user->soft_delete === 1) {
                $message = 'This account is already deleted.';
                return ApiResource::validationErrorResponse('Validation error!!', $message, self::STATUS_CODE_FORBIDDEN);
            }

            // Delete user account
            $deleteData = ['soft_delete' => 1];
            User::where('id', $userId)->update($deleteData);

            // Save close reason request
            CloseAccountMessage::create(['user_id' => $userId, 'close_reason' => $request->closure_reason, 'farewell_message' => $request->farewell_message]);

            // send notification to email confining account has been deleted
            $user->notify(new AccountDeleted($user->first_name." ".$user->last_name, $user->member_id));

            $message = 'Thank you for your feedback and for being part of the team.';
            $token = null;
            return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // Gets a single user full data
    public static function getSingleUserData($userId): JsonResponse
    {
        try {
            $userDetails = User::with('addressDetails', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')->where('id', $userId)->first();

            $message = 'User details retried successfully';
            $token = null;
            return ApiResource::successResponse($userDetails, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // Gets all users with their details in descending order
    public static function getAllUsersWithDetails($request): JsonResponse
    {
        return self::extracted($request);
    }

    // This method gets unverified users with their details
    public static function getUnverifiedUsers($request, $isApproved): JsonResponse
    {
        return self::extracted($request, $isApproved);
    }

    // This method gets only players from the database
    public static function getPlayersOnly($request, $isPlayer): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            $users = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                ->orderBy('id', 'DESC')
                ->where('user_type', $isPlayer)
                ->paginate($perPage, ['*'], 'page', $page);

            $message = 'All players with details retrieved successfully';
            $token = null;
            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method updates the team users medical details
    private static function updateMedicalDetails($request, $userId): void
    {
        $medicalDetails = MedicalDetail::where('user_id', $userId)->first();
        $medicalDetails->update([
            'injuries' => $request->injuries,
            'allergies' => $request->allergies,
            'medical_conditions' => $request->medical_conditions,
            'medications' => $request->medications,
            'gender' => $request->gender,
        ]);
    }

    // This method updates the team address details
    private static function updateAddressDetails($request, $userId): void
    {
        $addressDetails = UserAddress::where('user_id', $userId)->first();
        $addressDetails->update([
            'address' => $request->address,
            'city' => $request->city,
            'county_id' => $request->county_id,
            'region_id' => $request->region_id,
            'street_id' => $request->street_id,
            'coach_id' => $request->coach_id,
        ]);
    }

    // This method updates the team's educational details
    private static function updateEducationDetails($request, $userId): void
    {
        $educationDetails = EducationDetail::where('user_id', $userId)->first();
        $educationDetails->update([
            'school_level' => $request->school_level,
            'school_address' => $request->school_address,
            'school_city' => $request->school_city,
            'school_phone' => $request->school_phone,
            'school_email' => $request->school_email,
            'school_grade' => $request->school_grade,
            'school_counselor_name' => $request->school_counselor_name,
        ]);
    }

    // This method updates the team's other details
    private static function updateOtherDetails($request, $userId): void
    {
        $otherDetails = UserOtherDetail::where('user_id', $userId)->first();
        $otherDetails->update([
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'emergency_contact_email' => $request->emergency_contact_email,
            'emergency_notes' => $request->emergency_notes,
        ]);
    }

    // Retrieve users account
    public static function retrieveUserAccount($userId): JsonResponse
    {
        try {
            // Check if user is already approved
            $user = User::where('id', $userId)->first();
            if (!$user) return ApiResource::validationErrorResponse('Not Found!', 'User dose not exist in the system.', self::STATUS_CODE_NOT_FOUND);

            // Check if the account exists.
            if ($user->is_suspended === 0 && $user->soft_delete === 0 && $user->approved === 1) {
                $message = 'Account is active.';
                $token = null;
                return ApiResource::successResponse(User::where('id', $userId)->first(), $message, $token, self::STATUS_CODE_SUCCESS);
            }

            // Retrieve user account
            $retrieveData = ['is_suspended' => 0, 'soft_delete' => 0, 'approved' => 1];
            User::where('id', $userId)->update($retrieveData);

            $message = 'Account for user '.$user->first_name.' '.$user->last_name.' has been restored successfully. They can now login.';
            $token = null;
            return ApiResource::successResponse(User::where('id', $userId)->first(), $message, $token, self::STATUS_CODE_SUCCESS);

            // Trow system error messages
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets gender count for different users in the system.
    public static function getMaleFemaleCount($request): JsonResponse
    {
        try {
            // Count the number of male users
            $maleUsersCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Male');
            })->count();

            // Count the number of female users
            $femaleUsersCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Female');
            })->count();

            // Count the number of male players
            $malePlayerCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Male');
            })->where('user_type', 'player')->count();

            // Count the number of female players
            $femalePlayerCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Female');
            })->where('user_type', 'player')->count();

            // Count the number of female coaches
            $femaleCoachCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Female');
            })->where('user_type', 'coach')->count();

            // Count the number of male coaches
            $maleCoachCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Male');
            })->where('user_type', 'coach')->count();

            // Count the number of male social-workers
            $maleSocialWorkerCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Male');
            })->where('user_type', 'social_worker')->count();

            // Count the number of female social-workers
            $femaleSocialWorkerCount = User::whereHas('medicalDetails', function ($query) {
                $query->where('gender', 'Female');
            })->where('user_type', 'social_worker')->count();

            // Get graduated users
            $graduatedPlayers = User::where('is_graduated', 1)->count();

            // Other users count
            $others = User::where('user_type', 'User')->count();

            $genderCountArray = [
                'male_users' => $maleUsersCount,
                'female_users' => $femaleUsersCount,
                'male_players' => $malePlayerCount,
                'female_players' => $femalePlayerCount,
                'male_coach' => $maleCoachCount,
                'female_coach' => $femaleCoachCount,
                'male_social_worker' => $maleSocialWorkerCount,
                'female_social_worker' => $femaleSocialWorkerCount,
                'graduated_players' => $graduatedPlayers,
                'other_users_count' => $others,
            ];

            $message = 'Gender count fetched successfully';
            $token = null;
            return ApiResource::successResponse($genderCountArray, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets coaches data
    public static function getCoachData($request, $isCoach): JsonResponse
    {
        try {
            // Get the "page" query string parameter or default to page 1
            $page = $request->query('page', 1);
            $perPage = 10; // Number of items per page

            if ($isCoach !== null) {
                $users = User::where('user_type', $isCoach)
                    ->with('roles')
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage, ['*'], 'page', $page);

            }

            $message = 'All coaches retrieved successfully';
            $token = null;
            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets players and coaches names and ids
    public static function getPlayersAndCoaches($request): JsonResponse
    {
        try {
            // Get players and coaches from the User model
            $players = User::where('user_type', 'player')->select('id', 'first_name', 'last_name')->get();
            $coaches = User::where('user_type', 'coach')->select('id', 'first_name', 'last_name')->get();

            // You can create arrays with names and IDs here if needed
            $playersArray = CoachesPlayersResource::collection($players);
            $coachesArray = CoachesPlayersResource::collection($coaches);

            $message = 'Coaches and players fetched successfully';
            $token = null;
            $users = ['players' => $playersArray, 'coaches' => $coachesArray];

            return ApiResource::successResponse($users, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function updates the user account details
    public function updateUserAccountDetails($request, $userId): JsonResponse
    {
        $userData = [];
        $message = "";
        $fullPath = "";

        try {
            $validator = Validator::make(
                $request->all(),
                UserResource::validateAccountFields()
            );

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Find the user
            $user = User::find($userId);

            if (!$user) {
                return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);
            }

            // Check if there's an existing image
            if ($user->image) {
                // Delete the existing image
                $existingImagePath = public_path('assets/profile_account_images/') . basename($user->image);
                if (file_exists($existingImagePath)) {
                    unlink($existingImagePath);
                }
            }

            // Handle image upload and store the file
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $filename = "shama_Profile_".$request->first_name."_". time() . '.' . $uploadedFile->getClientOriginalExtension();
                $filePath = 'assets/profile_account_images/' . $filename; // Relative path within the public folder
                $fullPath = url($filePath); // Full path including the 'public' folder

                // Move and store the uploaded file
                $uploadedFile->move(public_path('assets/profile_account_images'), $filename);

                // You can save the $filePath in your database or return it in the response
                $message = 'File uploaded successfully to: ' . $fullPath;
            } else {
                $message = 'No file was uploaded.';
            }

            // Update user details in the database.
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->image = $fullPath;

            if ($user->save()) {
                $userData = $user;
                $message = 'Account details updated successfully.';
            }

            $token = null;
            return ApiResource::successResponse($userData, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function updates the users account password
    public function updateAccountPassword($request, $userId): JsonResponse
    {
        try {
            $validator = Validator::make(
                $request->all(),
                UserResource::validatePasswordFields());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Find the user by ID
            $user = User::find($userId);

            if (!$user) {
                return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);
            }

            // Check if the current password matches
            if (!Hash::check($request->input('old_password'), $user->password)) {
                return ApiResource::errorResponse('Current password is incorrect', self::STATUS_CODE_ERROR);
            }

            // Update the password
            $user->password = Hash::make($request->input('new_password'));
            $user->save();

            // Return a success response
            $message = 'Password updated successfully.';
            return ApiResource::successResponse([], $message, null, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method uploads user image
    public static function uploadUserImage($request, $userId): JsonResponse
    {
        try {
            $message = "";
            $fullPath = "";
            // Validate incoming image
            $validator = Validator::make(
                $request->all(),
                UserResource::validateUserProfileImage()
            );
            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'An image is required but was not provided';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }
            // Find the user with ID
            $user = User::find($userId);
            // Check if user with ID provided exists
            if (!$user) {
                return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);
            }
            // Check if there's an existing image
            if ($user->image) {
                // Delete the existing image
                $existingImagePath = public_path('assets/profile_account_images/') . basename($user->image);
                if (file_exists($existingImagePath)) {
                    unlink($existingImagePath);
                }
            }
            // Check if request has file the upload
            if ($request->hasFile('user_image')) {
                $uploadedFile = $request->file('user_image');
                $filename = "shama_Profile_".$user->first_name."_". time() . '.' . $uploadedFile->getClientOriginalExtension();
                $filePath = 'assets/profile_account_images/' . $filename; // Relative path within the public folder
                $fullPath = url($filePath); // Full path including the 'public' folder

                // Move and store the uploaded file
                $uploadedFile->move(public_path('assets/profile_account_images'), $filename);

                // You can save the $filePath in your database or return it in the response
                $message = 'File uploaded successfully to: ' . $fullPath;
            } else {
                $message = 'No file was uploaded.';
            }
            // Update image in the database
            $user->image = $fullPath;
            $user->save();

            return ApiResource::successResponse($user, $message, null, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getNewUserInformation($request, $userId): JsonResponse
    {
        try {
            $user = User::find($userId);
            // Check if user with ID provided exists
            if (!$user) {
                return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);
            }

            // Get team name
            $teamName = static::getTeamNameByUserId($user->id);
            $user->team_name = $teamName;

            $message = 'Updated information for '.$user->first_name.' '.$user->last_name.'! pulled successfully';
            $token = $user->createToken('api_token')->plainTextToken;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets users count for logged-in user
    public static function getLoggedInUsersCount($request, $teamId): JsonResponse
    {
        try {
            $uniqueUserIDs = TeamLocationUser::where('team_id', $teamId)
                ->distinct()
                ->pluck('user_id');

            $graduated = User::whereIn('id', $uniqueUserIDs)
                ->where('is_graduated', 1)
                ->count();

            $suspended = User::whereIn('id', $uniqueUserIDs)
                ->where('is_suspended', 1)
                ->count();

            $dataCount = [
                'total_players' => count($uniqueUserIDs),
                'total_graduated' => $graduated,
                'total_suspended' => $suspended,
            ];

            $message = 'User information count fetched';
            $token = null;
            return ApiResource::successResponse($dataCount, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getTeamNameByUserId($userId): string
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

                return $team->team_name;
            } else {
                return "User is not associated with any team in the teamLocationUser table.";
            }
        } else {
            return "User not found.";
        }
    }

    // This function updates the clients account details validateClientDetailsUpdate
    public static function updateClientUserAccountDetails($request, $userId): JsonResponse
    {
        try {
            // Validate incoming user request
            $validator = Validator::make(
                $request->all(),
                UserResource::validateClientDetailsUpdate()
            );
            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'Some fields are missing for client account update';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Find the user with ID
            $user = User::find($userId);
            // Check if user with ID provided exists
            if (!$user) {
                $message = "No user found for the provided ID";
                return ApiResource::errorResponse($message, self::STATUS_CODE_NOT_FOUND);
            }

            // Update user data request
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->age = $request->age;
            $user->facebook_link = $request->facebook_link;
            $user->twitter_link = $request->twitter_link;
            $user->instagram_link = $request->instagram_link;
            $user->updated_at = Carbon::now();
            $user->save();

            $message = 'Account details for '.$request->first_name." ".$request->last_name.' were updated successfully. You may need to logout for this changes to take effect.';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method gets a single player with details
    public static function getSinglePlayerWithDetails($request, $userId): JsonResponse
    {
        try {
            $user = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                ->where('id', $userId)
                ->first();

            $message = 'All details for '.$user->first_name." ".$user->last_name.' retrieved successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
