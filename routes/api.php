<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
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
        Route::get('/get-single-user-details/{user_id}', [UsersController::class, 'getSingleUserDetails']);
        Route::get('/get-users-with-details', [UsersController::class, 'getUsersWithDetails']);

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            // Endpoints for admin role
            Route::get('/get-permissions', [UsersController::class, 'getAllPermissions']); // Done!
            Route::post('/add-user-permission/{user_id}/{role_id}', [UsersController::class, 'grantUserPermission']); // Done!
            Route::post('/remove-user-permission/{user_id}/{role_id}', [UsersController::class, 'removeUserPermission']);
            Route::post('/create-permission', [UsersController::class, 'createNewPermission']);
            Route::get('/users', [UsersController::class, 'getUsers']); // Done!
        });

        Route::middleware('role:coach')->group(function () {
            // Endpoints for coach role
            Route::post('/approve-user/{user_id}', [UsersController::class, 'approveUser']);
            Route::post('/edit-user-details/{user_id}', [UsersController::class, 'editUserDetails']);
            Route::post('/cancel-approval/{user_id}', [UsersController::class, 'cancelApproval']);
            Route::post('/delete-account/{user_id}', [UsersController::class, 'deleteAccount']);
        });

        Route::middleware('role:team')->group(function () {
            // Endpoints for team role
        });
    });
});
