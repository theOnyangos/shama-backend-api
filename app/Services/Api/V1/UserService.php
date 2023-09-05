<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\EducationDetail;
use App\Models\MedicalDetail;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserOtherDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
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

    public static function addPermissionToUser($userId, $permissionId): JsonResponse
    {
        try {
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

    public static function removePermissionFromUser($userId, $permissionId): JsonResponse
    {
        try {
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
        $message = 'Permission created successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    // This function approves the users account
    public static function approveUserAccount($userId): JsonResponse
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

            $message = 'User account approved successfully';
            $token = null;
            return ApiResource::successResponse(User::where('id', $userId)->first(), $message, $token, self::STATUS_CODE_SUCCESS);
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
            } elseif ($isPlayer !== null) {
                $users = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                    ->orderBy('id', 'DESC')
                    ->where('user_type', $isPlayer)
                    ->paginate($perPage, ['*'], 'page', $page);
            } else {
                $users = User::with('addressDetails.county:id,county_name', 'addressDetails.region:id,region_name', 'addressDetails.street:id,street_name', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage, ['*'], 'page', $page);
            }


            $message = 'All users with details retrieved successfully';
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
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);

        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function cancels user approval
    public static function suspendUsersAccount($userId): JsonResponse
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

            $message = 'Account deleted successfully';
            $token = null;
            return ApiResource::successResponse(User::where('id', $userId)->first(), $message, $token, self::STATUS_CODE_SUCCESS);
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
}
