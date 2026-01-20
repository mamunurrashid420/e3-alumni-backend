<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    
    // Get membership application data
    $membershipApplication = \App\Models\MembershipApplication::where('email', $user->email)
        ->where('status', \App\Enums\MembershipApplicationStatus::Approved)
        ->latest()
        ->first();
    
    // Use UserResource and include membership application
    $userResource = new \App\Http\Resources\Api\UserResource($user);
    $userData = $userResource->toArray($request);
    
    // Add membership application data if available
    if ($membershipApplication) {
        $membershipApplicationResource = new \App\Http\Resources\Api\MembershipApplicationResource($membershipApplication);
        $userData['membership_application'] = $membershipApplicationResource->toArray($request);
    } else {
        $userData['membership_application'] = null;
    }
    
    return response()->json($userData);
})->middleware('auth:sanctum');

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Membership application routes (public)
Route::post('/membership-applications', [MembershipApplicationController::class, 'store']);

// Payment routes (public)
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/members/{memberId}/info', [PaymentController::class, 'getMemberInfo']);

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

    // Member management routes (super admin only)
    Route::get('/members', [UserController::class, 'index']);
    Route::get('/members/{user}', [UserController::class, 'show']);

    // Payment routes (super admin only)
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::put('/payments/{payment}', [PaymentController::class, 'update']);
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve']);
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject']);
});
