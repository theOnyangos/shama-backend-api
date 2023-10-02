<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        return $this->attendanceService = $attendanceService;
    }

    public function getUsersAttendance(Request $request, $userId): JsonResponse
    {
        return $this->attendanceService->getUsersAttendance($request, $userId);
    }

    // This function creates user attendance
    public function createUserAttendance(Request $request, $userId): JsonResponse
    {
        return $this->attendanceService->createNewAttendance($request, $userId);
    }

    public function updateAttendance(Request $request, $attendanceId): JsonResponse
    {
        return $this->attendanceService->updateSingleAttendance($request, $attendanceId);
    }

    // This function soft deletes attendance from the database
    public function softDeleteAttendance($attendanceId): JsonResponse
    {
        return $this->attendanceService->softDeleteAttendance($attendanceId);
    }
}
