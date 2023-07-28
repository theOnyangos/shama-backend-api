<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\EducationDetail;
use App\Models\MedicalDetail;
use App\Models\UserOtherDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;
use Spatie\Permission\Models\Role;

class AuthenticationService
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
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
            $user = User::where('email', $request->email)->first();

            // Check if user is approved
            if ($user->approved === 0) {
                Auth::logout();
                $message = 'Your account is not approved yet, contact administrator for approval.';
                return ApiResource::validationErrorResponse('Forbidden! Account inactive', $message, self::STATUS_CODE_FORBIDDEN);
            }

            // Return success response
            $message = 'Registration successful. Welcome back '.$user->first_name.' '.$user->last_name.'!';
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
            $customMessages = [
                'required' => 'Please provide the :attribute field to continue.',
                'email' => 'The :attribute must be a valid email address.',
                'unique' => 'The :attribute has already been taken.',
                'min' => 'The :attribute must be at least :min characters.',
                'string' => 'The :attribute must be a string.',
                'digits' => 'The :attribute must be exactly :digits digits. Example (07********)',
                'password.regex' => 'The :attribute must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
            ];

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:191',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|nullable|string|digits:10|unique:users,phone',
                'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
                'injuries' => 'string|nullable',
                'allergies' => 'required|string|nullable',
                'medical_conditions' => 'required|string|nullable',
                'medications' => 'required|string|nullable',
                'gender' => 'required|string|nullable',
                'address' => 'string|nullable',
                'city' => 'string|nullable',
                'county' => 'string|nullable',
                'region' => 'string|nullable',
                'street' => 'string|nullable',
                'coach_id' => 'required|string|nullable',
                'emergency_contact_name' => 'required|string|nullable',
                'emergency_contact_phone' => 'required|string|nullable|digits:10',
                'emergency_contact_email' => 'required|string|nullable',
                'emergency_notes' => 'required|string|nullable',
                'school_level' => 'string|nullable',
                'school_address' => 'string|nullable',
                'school_city' => 'string|nullable',
                'school_phone' => 'string|nullable|digits:10',
                'school_email' => 'string|nullable',
                'school_grade' => 'string|nullable',
                'school_counselor_name' => 'string|nullable'
            ], $customMessages);

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

            // Store users data
            $personalDetails = new User();
            $personalDetails['member_id'] = static::generateSequentialId();
            $personalDetails['first_name'] = $request->first_name;
            $personalDetails['last_name'] = $request->last_name;
            $personalDetails['email'] = $request->email;
            $personalDetails['phone'] = $request->phone;
            $personalDetails['password'] = Hash::make($request->password);
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

            // Return response
            $message = 'Registration was successful and is under review, you will be notified upon approval.';
            $token = null;
            return ApiResource::successResponse($personalDetails, $message, $token, self::STATUS_CODE_SUCCESS_CREATE);

            // Get system error messages if any
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
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

    // ========== STAFF REGISTRATION FUNCTION ============
    public static function registerNewStaff($request): JsonResponse
    {
        // Run Validation
        $customMessages = [
            'required' => 'Please provide the :attribute field to continue.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'min' => 'The :attribute must be at least :min characters.',
            'string' => 'The :attribute must be a string.',
            'digits' => 'The :attribute must be exactly :digits digits. Example (07********)',
            'password.regex' => 'The :attribute must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
            'regex' => 'The :attribute format is invalid.',
        ];

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|regex:/^[A-Za-z]+( [A-Za-z]+)+$/',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|nullable|string|digits:10|unique:users,phone',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
        ], $customMessages);

        // Return error message if one or more input fields are empty
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'errors' => $validator->errors(),
                'message' => 'One or more inputs have errors, please check that all required inputs are filled and try again.'
            ], 422);
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
        $staffData['password'] = Hash::make($request->password);
        $staffData->save();

        // Return response
        return response()->json([
            'status' => 'success',
            'status_code' => 201,
            'data' => $staffData,
            'message' => 'Congratulations! '.$fullName.', your registration was successful and is under review, you will be notified upon approval.'
        ], 201);
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
}
