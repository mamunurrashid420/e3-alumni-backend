<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Membership application routes (public)
Route::post('/membership-applications', [MembershipApplicationController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Token management routes
    Route::prefix('tokens')->group(function () {
        Route::get('/', [TokenController::class, 'index']);
        Route::post('/', [TokenController::class, 'store']);
        Route::delete('/{tokenId}', [TokenController::class, 'destroy']);
        Route::delete('/', [TokenController::class, 'destroyAll']);
    });

    // Membership application routes (super admin only)
    Route::get('/membership-applications', [MembershipApplicationController::class, 'index']);
    Route::get('/membership-applications/{membershipApplication}', [MembershipApplicationController::class, 'show']);
    Route::put('/membership-applications/{membershipApplication}', [MembershipApplicationController::class, 'update']);
    Route::post('/membership-applications/{membershipApplication}/approve', [MembershipApplicationController::class, 'approve']);
    Route::post('/membership-applications/{membershipApplication}/reject', [MembershipApplicationController::class, 'reject']);
});
