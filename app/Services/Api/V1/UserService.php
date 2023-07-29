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
            $user = User::where('id', $userId)->update($approveData);

            $message = 'User account approved successfully';
            $token = null;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function update the team's user details.
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

    public static function cancelApprovalRequest($request, $userId): JsonResponse
    {
        $message = 'Account suspended successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

    public static function deleteAccountRequest($request, $userId): JsonResponse
    {
        $message = 'Account deleted successfully';
        $token = null;
        return ApiResource::successResponse([], $message, $token, self::STATUS_CODE_SUCCESS);
    }

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

    public static function getAllUsersWithDetails(): JsonResponse
    {
        try {
            $users = User::with('addressDetails', 'medicalDetails', 'educationDetails', 'otherDetails', 'roles')->get();

            $message = 'All users with details retried successfully';
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

}
