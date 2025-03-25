<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Protected routes
Route::group(['middleware' => 'auth:api'], function () {
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
    
    // User Profile routes
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [UserProfileController::class, 'profile']);
        Route::put('/', [UserProfileController::class, 'update']);
        Route::post('/avatar', [UserProfileController::class, 'updateAvatar']);
        Route::put('/password', [UserProfileController::class, 'updatePassword']);
        Route::put('/notifications', [UserProfileController::class, 'updateNotificationPreferences']);
        Route::put('/privacy', [UserProfileController::class, 'updatePrivacySettings']);
        Route::get('/dashboard', [UserProfileController::class, 'dashboard']);
        Route::get('/notifications', [UserProfileController::class, 'notifications']);
        Route::put('/notifications/{id}', [UserProfileController::class, 'markNotificationAsRead']);
    });
    
    // Public user profiles
    Route::get('/users/{id}', [UserProfileController::class, 'show']);
    
    // Other protected routes will go here
});