<?php

namespace App\Services\Api\V1;

use App\Helpers\ActivityHelper;
use App\Helpers\NotificationHelper;
use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\Activity;
use App\Models\Team;
use App\Models\TeamLocationUser;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\EducationDetail;
use App\Models\MedicalDetail;
use App\Models\UserOtherDetail;
use App\Notifications\AccountCreated;
use App\Notifications\NotifyAdmin;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AuthenticationService
{
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_SERVER = 500;

    // ================= LOGIN FUNCTION ==============
    public static function loginUser($request): JsonResponse
    {
        try {
            $validateUser = Validator::make($request->all(), [
               'email' => 'required|email',
               'password' => 'required'
            ]);

            // Validate Form Fields
            if ($validateUser->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validateUser->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Validate Login Credentials
            if (!Auth::attempt($request->only(['email', 'password']))) {
                $message = 'Invalid login details. Please use the correct details you provided at the time of registration.';
                return ApiResource::validationErrorResponse('Validation error', $message, self::STATUS_CODE_ERROR);
            }

            // Get user
            $user = User::with('roles')->where('email', $request->email)->first();

            // Check if user is approved
            if ($user->approved === 0) {
                Auth::logout();
                $message = 'Your account is not approved yet, contact administrator for approval.';
                return ApiResource::validationErrorResponse('Forbidden! Account inactive', $message, self::STATUS_CODE_FORBIDDEN);
            }

            // Check if user deleted their account
            if ($user->soft_delete === 1) {
                Auth::logout();
                $message = 'We couldn\'t find an account associated with the provided credentials. It\'s possible that your account has been deleted. If you wish to use our services again, please create a new account.';
                return ApiResource::validationErrorResponse('Account Not Found!', $message, self::STATUS_CODE_NOT_FOUND);
            }

            if ($user->is_suspended === 1) {
                Auth::logout();
                $message = 'This account is suspended due to violation of terms of service. Please contact support for assistance.';
                return ApiResource::validationErrorResponse('Account Suspended!', $message, self::STATUS_CODE_NOT_FOUND);
            }

            // Get team name
            $teamName = static::getTeamNameByUserId($user->id);
            if (!empty($teamName)) {
                $user->team_name = $teamName['team_name'];
                $user->team_id = $teamName['team_id'];
            } else {
                $user->team_name = null;
                $user->team_id = null;
            }


            $userName = ActivityHelper::getUserName($user->id);
//            $recentActivity = Activity::getRecentActivities($user->id);
//            $createdAt = Carbon::parse($recentActivity->created_at);
//            $timeAgo = $createdAt->diffForHumans();
            ActivityHelper::logActivity($user->id, $userName." logged in the application");

            // Return success response
            $message = 'Login successful. Welcome back '.$user->first_name.' '.$user->last_name.'!';
            $token = $user->createToken('api_token')->plainTextToken;
            return ApiResource::successResponse($user, $message, $token, self::STATUS_CODE_SUCCESS);

            // Trow System error if any
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // ================= TEAM REGISTRATION FUNCTION ================
    public function registerNewUsers($request): JsonResponse
    {
        try {
            // Run Validation
            $validator = Validator::make(
                $request->all(),
                UserResource::teamValidationFields(),
                UserResource::customValidationMessages());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Check if a user with the same email already exists
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                $message = 'A user with the same email already exists.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            $password = static::generatePassword(8);

            // Store users data
            $personalDetails = new User();
            $personalDetails['member_id'] = static::generateSequentialId();
            $personalDetails['first_name'] = $request->first_name;
            $personalDetails['last_name'] = $request->last_name;
            $personalDetails['category_id'] = $request->category_id ?? NULL;
            $personalDetails['email'] = $request->email;
            $personalDetails['phone'] = $request->phone;
            $personalDetails['age'] = $request->age;
            $personalDetails['user_type'] = "player";
            $personalDetails['password'] = Hash::make($password);
            $personalDetails->save();

            // Get the created ID
            $userId = $personalDetails->id;
            // Store Medical Details
            static::storeMedicalDetails($request, $userId);
            // Store Address Details
            static::storeAddressDetails($request, $userId);
            // Store Education Details
            static::storeEducationDetails($request, $userId);
            // Store User Other Details
            static::storeOtherDetails($request, $userId);

            // Assign the default role "team" to the user
            $teamRole = Role::where('name', 'team')->first();
            if ($teamRole) {
                $personalDetails->assignRole($teamRole);
            }

            // Send notification to users email
            $personalDetails->notify(new AccountCreated($request->first_name." ".$request->last_name, $request->email, $password));

            $userName = ActivityHelper::getUserName($request->user_id ?? 1);
            ActivityHelper::logActivity($request->user_id ?? 1, $userName." Registered a new team player: ".$request->first_name." ".$request->last_name);

            // Return success response
            $message = 'Registration was successful and is under review, you will be notified upon approval.';
            $token = null;
            return ApiResource::successResponse($personalDetails, $message, $token, self::STATUS_CODE_SUCCESS_CREATE);

            // Get system error messages if any
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

    // ========== STAFF REGISTRATION FUNCTION ============
    public static function registerNewStaff($request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            UserResource::staffValidationFields(),
            UserResource::customValidationMessages());

        // Return error message if one or more input fields are empty
        if ($validator->fails()) {
            $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
            return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
        }

        // Validation passed, now split the full name into first name and last name
        $fullName = $request->input('full_name');
        $nameParts = explode(' ', $fullName, 2); // Split into an array, limiting to 2 elements

        $firstName = $nameParts[0]; // First element is the first name
        $lastName = $nameParts[1] ?? ''; // Second element is the last name (if available)

        // Store user data
        $staffData = new User();
        $staffData['member_id'] = static::generateSequentialId();
        $staffData['first_name'] = $firstName;
        $staffData['last_name'] = $lastName;
        $staffData['email'] = $request->email;
        $staffData['phone'] = $request->phone;
        $staffData['user_type'] = $request->user_type;
        $staffData['password'] = Hash::make($request->password);
        $staffData->save();

        $teamRole = "";
        if ($request->user_type === 'coach' || $request->user_type === 'user' || $request->user_type === 'teacher' || $request->user_type === 'social_worker') {
            $teamRole = Role::where('name', 'coach')->first();
        }

        if ($request->user_type === 'admin') {
            $teamRole = Role::where('name', 'admin')->first();
        }

        if ($teamRole) {
            $staffData->assignRole($teamRole);
        }

        $adminUser = [
            "user_name" => "Admin",
            "email" => "denonyango@gmail.com"
        ];

        // Get admin users
        $notificationMessage = "A new user ".$fullName." has registered for an account as a staff member. In order for them to be able to access their newly created account, please login the app and approve this account";

        // Send notification to users email
        $staffData->notify(new AccountCreated($fullName));

        // Return response
        $message = 'Congratulations! '.$fullName.', your registration was successful and is under review, you will be notified upon approval.';
        $token = $staffData->createToken('api_token')->plainTextToken;
        \Notification::route('mail', $adminUser['email'])->notify(new NotifyAdmin($notificationMessage, $adminUser['user_name']));
        return ApiResource::successResponse($staffData, $message, $token, self::STATUS_CODE_SUCCESS_CREATE);
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

    public static function getTeamNameByUserId($userId): array
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
}


