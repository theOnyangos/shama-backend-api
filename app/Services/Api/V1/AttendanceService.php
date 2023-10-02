<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceService
{
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_SERVER = 500;
    public function getUsersAttendance($request, $userId): JsonResponse
    {
        try {
            // Replace 'coach_id' with the actual foreign key relationship in your attendance table.
            $coachId = $userId;

            // Retrieve attendance records with associated users based on the user IDs in 'attendees'.
            $attendances = Attendance::where('coach_id', $coachId)
                ->where('soft_delete', 0)
                ->orderBy('id', 'desc')
                ->get();

            // Create an empty array to store the results.
            $result = [];

            foreach ($attendances as $attend) {
                // Get the user IDs from the 'attendees' field as an array.
                $userIds = json_decode($attend->attendees);

                // Retrieve the users associated with the user IDs.
                $users = User::select('id', 'first_name', 'last_name', 'image', 'email', 'phone', 'member_id')->whereIn('id', $userIds)->get();

                // Add the attendance record along with associated users to the result array.
                $result[] = [
                    'attendance' => $attend,
                    'users' => $users,
                ];
            }

            // Process the results as needed and return in the response.
            $message = 'User attendance fetched successfully.';
            $token = null;

            return ApiResource::successResponse($result, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function handles creation of new attendance
    public function createNewAttendance($request, $userId): JsonResponse
    {
        try {
            $validator = Validator::make(
                $request->all(),
                UserResource::validateAttendanceCreation());

            // Return error message if one or more input fields are empty
            if ($validator->fails()) {
                $message = 'Please check your inputs for validation errors.';
                return ApiResource::validationErrorResponse($validator->errors(), $message, self::STATUS_CODE_ERROR);
            }

            // Check if user is already approved
            $user = User::where('id', $userId)->first();
            if (!$user) {
                $message = 'Account not found!';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            $attendance = new Attendance();
            $attendance['coach_id'] = $userId;
            $attendance['team_id'] = $request->teams;
            $attendance['attendance_type'] = $request->attendance_type;
            $attendance['attendees'] = $request->players;
            $attendance['description'] = $request->description;
            $attendance->save();

            // Process the results as needed and return in the response.
            $message = 'Attendance added successfully.';
            $token = null;
            return ApiResource::successResponse($attendance, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function updates the users array in the attendance table
    public static function updateSingleAttendance($request, $attendanceId): JsonResponse
    {
        try {

            if (!$request->has('players')) {
                $message = 'The request has no value';
                return ApiResource::validationErrorResponse([], $message, self::STATUS_CODE_ERROR);
            }

            $newUsersIds = json_decode($request->players);

            $attendance = Attendance::find($attendanceId);

            if (!$attendance) {
                $message = 'Attendance not found!';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            $attendance->attendees = $newUsersIds;
            $attendance->updated_at = Carbon::now();
            $attendance->soft_delete = empty($newUsersIds) ? 1 : 0;
            $attendance->save();

            // Process the results as needed and return in the response.
            $message = 'Attendance with ID.'.$attendanceId." has been updated successfully";
            $token = null;

            return ApiResource::successResponse($newUsersIds, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This method soft-deletes attendance from the database
    public static function softDeleteAttendance($attendanceId): JsonResponse
    {
        try {
            $attendance = Attendance::find($attendanceId);

            if (!$attendance) {
                $message = 'Attendance not found!';
                return ApiResource::validationErrorResponse('Validation error!', $message, self::STATUS_CODE_ERROR);
            }

            $attendance->soft_delete = 1;
            $attendance->updated_at = Carbon::now();
            $attendance->save();

            // Process the results as needed and return in the response.
            $message = 'Attendance deleted successfully';
            $token = null;

            return ApiResource::successResponse($attendance, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
