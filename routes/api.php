<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\UsersController;
use Illuminate\Support\Facades\Route;

// Prefix all routes with '/api'
Route::prefix('v1')->group(function () {
    // Authentication Routes (Public Endpoints)
    Route::post('/auth-team-registration', [AuthController::class, 'createNewRegistration']); // Done!
    Route::post('/auth-staff-registration', [AuthController::class, 'createNewStaffRegistration']); // Done!
    Route::post('/auth-login', [AuthController::class, 'login']); // Done!
    Route::get('/counties', [AddressController::class, 'getAllCounties']);
    Route::get('/regions', [AddressController::class, 'getAllRegionsInCounties']);
    Route::get('/streets', [AddressController::class, 'getAllStreetsInRegion']);

    Route::middleware('auth:sanctum')->group(function () {
        // General routes
        Route::post('/logout', [AuthController::class, 'logout']); // Done!
        Route::get('/get-users-with-details', [UsersController::class, 'getUsersWithDetails']); // Done!

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            // Endpoints for admin role
            Route::get('/get-permissions', [UsersController::class, 'getAllPermissions']); // Done!
            Route::post('/add-user-permission/{user_id}/{role_id}', [UsersController::class, 'grantUserPermission']); // Done!
            Route::post('/remove-user-permission/{user_id}/{role_id}', [UsersController::class, 'removeUserPermission']); // Done!
            Route::post('/create-permission', [UsersController::class, 'createNewPermission']); // Done!
            Route::get('/users', [UsersController::class, 'getUsers']); // Done!

            Route::post('/create-team/{admin_id}', [TeamController::class, 'createNewTeam']); // Almost Done!
            Route::put('/add-player-to-team/{admin_id}/{team_id}', [TeamController::class, 'addNewTeamMember']); // Done!
            Route::put('/add-multiple-team-member/{team_id}', [TeamController::class, 'addMultipleTeamMember']); // Done!
            Route::post('/update-team-account/{team_id}', [TeamController::class, 'updateTeamAccountDetails']); // Done!

            Route::put('/delete-team-account/{team_id}', [TeamController::class, 'deleteTeamAccountDetails']); // Incomplete
            Route::delete('/delete-team-account/{team_id}', [TeamController::class, 'permanentDeleteTeamAccountDetails']); // Incomplete
            Route::get('/get-account-details/{team_id}', [TeamController::class, 'getAccountDetails']); // Incomplete
            Route::get('/get-team-members/{team_id}', [TeamController::class, 'getTeamPlayers']); // Incomplete

            Route::get('/get-unapproved-members', [TeamController::class, 'getUnapprovedMembers']);
            Route::get('/get-coaches', [TeamController::class, 'getAllCoaches']);
            Route::get('/get-team-weekly-attendance', [TeamController::class, 'getTeamWeeklyAttendance']);
            Route::get('/get-graduated-team', [TeamController::class, 'getGraduatedTeamMembers']);
            Route::get('/get-notifications', [NotificationsController::class, 'getAllNotifications']);
            Route::get('/get-unread-notifications', [NotificationsController::class, 'getUnreadNotifications']);
            Route::delete('/delete-notification', [NotificationsController::class, 'getUnreadNotifications']);
            Route::put('/update-admin-account-details', [UsersController::class, 'updateAdminAccountDetails']);
            Route::put('/update-account-password', [UsersController::class, 'updateAccountPassword']);
            Route::put('/update-profile-image', [UsersController::class, 'updateProfileImage']);
            Route::put('/retrieve-account/{user_id}', [UsersController::class, 'retrieveAccount']); // Done!
        });

        Route::middleware('role:coach')->group(function () {
            // Endpoints for coach role
            Route::post('/approve-user/{user_id}', [UsersController::class, 'approveUser']); // Done!
            Route::get('/get-single-user-details/{user_id}', [UsersController::class, 'getSingleUserDetails']); // Done!
            Route::put('/update-user-details/{user_id}', [UsersController::class, 'updateUserDetails']); // Done!
            Route::put('/suspend-account/{user_id}', [UsersController::class, 'suspendAccount']); // Done!
            Route::put('/delete-account/{user_id}', [UsersController::class, 'deleteAccount']);
        });

        Route::middleware('role:team')->group(function () {
            // Endpoints for team role
        });
    });
});
