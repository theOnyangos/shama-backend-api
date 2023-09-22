<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\PasswordVerification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class NotificationService
{
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_SERVER = 500;

    // This method sends verification code to user
    public static function sendVerificationCode($email): JsonResponse
    {
        try {
            // Get user by email
            $user = User::where('email', $email)->first();
            // Check if user with ID provided exists
            if (!$user) {
                return ApiResource::errorResponse('User with email: '.$email.' is not registered as a member of (SRF)', self::STATUS_CODE_NOT_FOUND);
            }

            $verificationCode = static::generateRandomNumbers();

            $message['first'] = "Your verification code is: ".$verificationCode;
            $message['second'] = "This code will expire in 30 minutes.";

            $user->notify( new PasswordVerification($user->last_name, $message));
            $user->verification_code = $verificationCode;

            $expirationTime = Carbon::now();
            $user->verification_code_expires_at = $expirationTime;
            $user->save();

            // Return response
            $message = 'Verification code sent to '.$user->email.' successfully.';
            return ApiResource::successResponse($user, $message, null, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    /**
     * @throws \Exception
     */
    private static function generateRandomNumbers($min = 100000, $max = 999999): int
    {
        return rand($min, $max);
    }

    // This function compares verification code the user provides with that in the database
    public static function checkVerificationCode($request, $userEmail): JsonResponse
    {
        try {
            $verificationCode = $request->input('code');

            // Get user by email
            $user = User::where('email', $userEmail)->first();
            // Check if user with ID provided exists
            if (!$user) {
                return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);
            }

            // Check if time expired
            $currentTime = Carbon::now();
            $expirationTime = $user->verification_code_expires_at;

            // Calculate the time difference in minutes
            $minutesDifference = $currentTime->diffInMinutes($expirationTime);

            if ($minutesDifference > 30) {
                // The verification code has expired
                return ApiResource::errorResponse('Verification code '.$verificationCode.' has expired.', self::STATUS_CODE_NOT_FOUND);
            }

            // Check if the provided verification code matches the user's stored code
            if ($verificationCode != $user->verification_code) {
                return ApiResource::errorResponse('Invalid verification code '.$verificationCode. ' - '.$user->verification_code, self::STATUS_CODE_ERROR);
            }

            $user->verification_code = NULL;
            $user->verification_code_expires_at = NULL;
            $user->save();

            // Return response
            $message = 'Code verified successfully.';
            return ApiResource::successResponse($user, $message, null, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function updates the new user password in the database
    public static function updateNewUserPassword($request, $userEmail): JsonResponse
    {
        try {
            $validator = Validator::make(
                $request->all(),
                UserResource::validateClientUpdatePassword());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'Password validation failed check your inputs and try again.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Get user by email
            $user = User::where('email', $userEmail)->first();
            // Check if user with ID provided exists
            if (!$user) {
                return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);
            }

            // Update the password
            $user->password = Hash::make($request->input('new_password'));
            $user->save();

            // Return response
            $message = 'Password updates successfully you may proceed to login.';
            return ApiResource::successResponse($user, $message, null, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
