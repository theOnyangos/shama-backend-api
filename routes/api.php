<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CoachesController;
use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\PermissionsController;
use App\Http\Controllers\Api\V1\PlayersController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\StatisticalDataController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Prefix all routes with '/api'
Route::prefix('v1')->group(function () {
    // Authentication Routes (Public Endpoints)
    Route::post('/auth-team-registration', [AuthController::class, 'createNewRegistration']); // Done!
    Route::post('/auth-staff-registration', [AuthController::class, 'createNewStaffRegistration']); // Done!
    Route::post('/auth-login', [AuthController::class, 'login']); // Done!
    Route::get('/counties', [AddressController::class, 'getAllCounties']);
    Route::get('/regions/{county_id}', [AddressController::class, 'getAllRegionsInCounties']);
    Route::get('/streets/{region_id}', [AddressController::class, 'getAllStreetsInRegion']);
    Route::post('/suspend-user-account/{user_id}', [UsersController::class, 'suspendAccount']); // Done!
    Route::post('/approve-user-account/{user_id}', [UsersController::class, 'approveUser']); // Done!
    Route::post('/retrieve-account/{user_id}', [UsersController::class, 'retrieveAccount']); // Done!
    Route::post('/delete-account/{user_id}', [UsersController::class, 'deleteAccount']);
    Route::get('/get-team-name/{user_id}', [AuthController::class, 'getTeamName']);
    Route::post('/send-verification-code/{user_email}', [NotificationsController::class, 'sendPasswordVerificationCode']); // Done
    Route::get('/check-verification-code/{user_email}', [NotificationsController::class, 'checkVerificationCode']); // Done
    Route::post('/update-user-password/{user_email}', [NotificationsController::class, 'updateUserPassword']); // Done
    Route::post('/send-delete-account-confirmation/{user_email}', [NotificationsController::class, 'sendDeleteAccountConfirmation']); // Done
    Route::get('/save-closure-reason/{user_id}', [HomeController::class, 'openCloseAccountPage']);

    Route::middleware('auth:sanctum')->group(function () {
        // General routes
        Route::post('/logout', [AuthController::class, 'logout']); // Done!
        Route::get('/get-users-with-details', [UsersController::class, 'getUsersWithDetails']); // Done!
        Route::post('/update-user-account/{user_id}', [UsersController::class, 'updateUserAccount']);
        Route::post('/update-account-password/{user_id}', [UsersController::class, 'updateAccountPassword']);
        Route::post('/upload-user-image/{user_id}', [UsersController::class, 'uploadUserProfileImage']); // Done
        Route::get('/get-updated-user-information/{user_id}', [UsersController::class, 'getUpdatedUserInformation']); // Done
        Route::get('/get-user-count/{team_id}', [UsersController::class, 'getLoggedInUsersCount']); // Done
        // Route::get('/get-single-player-with-details/{user_id}', [UsersController::class, 'getSinglePlayerWithDetails']); // Done
        // New Routes 13/11/2023
        Route::post('/create-player-category/{user_id}', [HomeController::class, 'createNewCategory']);
        Route::get('/get-categories', [HomeController::class, 'getAllCategories']);
        Route::get('/get-players-by-category/{category_id}', [HomeController::class, 'getPlayersByCategory']);
        Route::post('/upload-documents/{user_id}/{player_id}', [HomeController::class, 'uploadPlayerDocuments']);

        // New Routes 14/11/2023
        Route::get('/get-users-activities', [HomeController::class, 'getUsersActivities']);

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            // Endpoints for admin role
            Route::get('/get-permissions', [UsersController::class, 'getAllPermissions']); // Done!
            Route::post('/add-user-permission', [UsersController::class, 'grantUserPermission']); // Done!
            Route::post('/remove-user-permission', [UsersController::class, 'removeUserPermission']); // Done!
            Route::post('/create-permission', [UsersController::class, 'createNewPermission']); // Done!
            Route::get('/users', [UsersController::class, 'getUsers']); // Done!
            Route::get('/statistical-data', [StatisticalDataController::class, 'getStatisticalData']); // Done!
            Route::get('/unverified-users-data', [UsersController::class, 'getUnverifiedUsersWithDetails']); // Done!
            Route::get('/get-players-data', [UsersController::class, 'getPlayersData']); // Done!
            Route::get('/get-male-female-count', [UsersController::class, 'getMaleAndFemaleCount']); // Done!
            Route::get('/get-coaches-data', [UsersController::class, 'getCoachesData']); // Done!
            Route::get('/get-coaches-and-players', [UsersController::class, 'getCoachesAndPlayersData']); // Done!

            Route::get('/get-new-players', [PlayersController::class, 'getAllNewPlayers']); // Done
            Route::get('/get-players', [PlayersController::class, 'getAllPlayers']); // Done
            Route::get('/get-graduated-players', [PlayersController::class, 'getAllGraduatedPlayers']); // Done
//            Route::post('/upload-user-image/{user_id}', [UsersController::class, 'uploadUserProfileImage']); // Done
            Route::get('/get-team-players/{team_id}', [TeamController::class, 'getAllTeamPlayers']); // Done
            Route::get('/get-team-coaches/{team_id}', [TeamController::class, 'getAllTeamCoaches']); // Done

            Route::post('/create-team/{admin_id}', [TeamController::class, 'createNewTeam']); // Almost Done!
            Route::put('/add-player-to-team/{admin_id}/{team_id}', [TeamController::class, 'addNewTeamMember']); // Done!
            Route::put('/add-multiple-team-member/{team_id}', [TeamController::class, 'addMultipleTeamMember']); // Done!
            Route::post('/update-team-account/{team_id}', [TeamController::class, 'updateTeamAccountDetails']); // Done!

            Route::put('/delete-team-account/{team_id}', [TeamController::class, 'deleteTeamAccountDetails']); // Incomplete
            Route::delete('/delete-team-account/{team_id}', [TeamController::class, 'permanentDeleteTeamAccountDetails']); // Incomplete
            Route::get('/get-account-details/{team_id}', [TeamController::class, 'getAccountDetails']); // Incomplete
            Route::get('/get-team-members/{team_id}', [TeamController::class, 'getTeamPlayers']); // Incomplete
            Route::get('/get-all-team-locations-data', [TeamController::class, 'getAllTeamLocationData']); // Incomplete
            Route::post('/create-role-permission', [PermissionsController::class, 'createNewRolePermission']); // Done
            Route::get('/get-roles-permissions', [PermissionsController::class, 'getRolesWithPermission']); // Done
            Route::get('/search-users', [SearchController::class, 'getUserSearchData']); // Done

            Route::get('/get-unapproved-members', [TeamController::class, 'getUnapprovedMembers']);
            Route::get('/get-coaches', [TeamController::class, 'getAllCoaches']);
            Route::get('/get-team-weekly-attendance', [TeamController::class, 'getTeamWeeklyAttendance']);
            Route::get('/get-graduated-team', [TeamController::class, 'getGraduatedTeamMembers']);
            Route::get('/get-notifications', [NotificationsController::class, 'getAllNotifications']);
            Route::get('/get-unread-notifications', [NotificationsController::class, 'getUnreadNotifications']);
            Route::delete('/delete-notification', [NotificationsController::class, 'getUnreadNotifications']);
            Route::put('/update-admin-account-details', [UsersController::class, 'updateAdminAccountDetails']);
            Route::put('/update-profile-image', [UsersController::class, 'updateProfileImage']);
        });

        Route::middleware('role:coach')->group(function () {
            // Endpoints for coach role
//            Route::post('/approve-user/{user_id}', [UsersController::class, 'approveUser']); // Done!
            Route::get('/get-single-user-details/{user_id}', [UsersController::class, 'getSingleUserDetails']); // Done!
            Route::put('/update-user-details/{user_id}', [UsersController::class, 'updateUserDetails']); // Done!
//            Route::put('/suspend-account/{user_id}', [UsersController::class, 'suspendAccount']); // Done!
            Route::get('/get-team-players-not-graduated/{team_id}', [TeamController::class, 'getPlayersNotGraduated']); // Done!
            Route::get('/get-team-names-ids', [TeamController::class, 'getAllTeamNamesWithIds']); // Done!
            Route::get('/get-users-attendance/{user_id}', [AttendanceController::class, 'getUsersAttendance']); // Done!
            Route::post('/create-users-attendance/{user_id}', [AttendanceController::class, 'createUserAttendance']); // Done!
            Route::post('/update-attendance/{attendance_id}', [AttendanceController::class, 'updateAttendance']); // Done!
            Route::delete('/delete-attendance/{attendance_id}', [AttendanceController::class, 'softDeleteAttendance']); // Done!
            Route::put('/update-user-account/{user_id}', [UsersController::class, 'updateUserAccountDetails']); // Done!
            Route::post('/create-new-player/{user_id}', [PlayersController::class, 'registerNewPlayer']); // Done!
            Route::get('/get-client-team-players/{team_id}', [TeamController::class, 'getAllTeamPlayers']); // Done
            Route::get('/get-client-graduated-players/{team_id}', [TeamController::class, 'getAllClientGraduatedPlayers']); // Done
            Route::get('/get-single-player-with-details/{user_id}', [UsersController::class, 'getSinglePlayerWithDetails']); // Done
            Route::put('/update-single-player-details/{user_id}', [PlayersController::class, 'updateSinglePlayerDetails']); // Done
            Route::get('/get-client-players/{team_id}', [TeamController::class, 'getGraduatedAndNotGraduatedPlayers']); // Done
            Route::get('/search-client-players/{team_id}', [TeamController::class, 'searchClientPlayers']); // Done
        });

        Route::middleware('role:team')->group(function () {
            // Endpoints for team role
        });
    });
});
