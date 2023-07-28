<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

// Prefix all routes with '/api'
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::post('/auth-team-registration', [AuthController::class, 'createNewRegistration']);
    Route::post('/auth-staff-registration', [AuthController::class, 'createNewStaffRegistration']);
    Route::post('/auth-login', [AuthController::class, 'login']);

    Route::get('/users', function () {
       return "This route is reached";
    });

    Route::middleware('auth:sanctum')->group(function () {
        // Your authenticated API routes here
    });

    // Other authenticated routes for different roles can be added here with appropriate middleware
    Route::middleware('role:admin')->group(function () {
        // Endpoints for admin role
    });

    Route::middleware('role:team')->group(function () {
        // Endpoints for team role
    });

    Route::middleware('role:coach')->group(function () {
        // Endpoints for coach role
    });
});
