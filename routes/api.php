<?php

use App\Http\Controllers\Api\AdvisoryBodyMemberController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchRepresentativeController;
use App\Http\Controllers\Api\ConveningCommitteeMemberController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HonorBoardEntryController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\MemberTypeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicMemberController;
use App\Http\Controllers\Api\ScholarshipApplicationController;
use App\Http\Controllers\Api\ScholarshipController;
use App\Http\Controllers\Api\SelfDeclarationController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\OptionalSanctumAuth;
use Illuminate\Support\Facades\Route;

Route::get('/user', [UserController::class, 'showCurrentUser'])->middleware('auth:sanctum');

Route::put('/user', [UserController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::match(['put', 'post'], '/user/profile', [UserController::class, 'updateMemberProfile'])->middleware('auth:sanctum');

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Membership application routes (public)
Route::post('/membership-applications', [MembershipApplicationController::class, 'store']);

// Member types route (public)
Route::get('/member-types', [MemberTypeController::class, 'index']);

// Scholarships (public read active; index checks auth for admin)
Route::get('/scholarships', [ScholarshipController::class, 'index']);

// Scholarship application (public submit; user_id set if authenticated)
Route::post('/scholarship-applications', [ScholarshipApplicationController::class, 'store']);

// About Us content (public read)
Route::get('/about/members', [PublicMemberController::class, 'index']);
Route::get('/about/convening-committee', [ConveningCommitteeMemberController::class, 'index']);
Route::get('/about/advisory-body', [AdvisoryBodyMemberController::class, 'index']);
Route::get('/about/honor-board', [HonorBoardEntryController::class, 'index']);
Route::get('/about/batch-representatives', [BatchRepresentativeController::class, 'index']);

// Payment routes (public)
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/members/{memberId}/info', [PaymentController::class, 'getMemberInfo']);

// Downloads (public read)
Route::get('/downloads', [DownloadController::class, 'index']);

// Events (public read)
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}/registrations', [EventController::class, 'registrations'])->middleware('auth:sanctum');
Route::get('/events/{event}', [EventController::class, 'show'])->middleware(OptionalSanctumAuth::class);
Route::post('/events/{event}/register-guest', [EventController::class, 'registerGuest']);

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
    Route::put('/members/{user}', [UserController::class, 'update']);
    Route::put('/members/{user}/profile', [UserController::class, 'updateMemberProfileForMember']);
    Route::post('/members/{user}/resend-sms', [UserController::class, 'resendSms']);
    Route::post('/members/{user}/renew-membership', [UserController::class, 'renewMembership']);

    // Payment routes (super admin only)
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/summary', [PaymentController::class, 'summary']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::put('/payments/{payment}', [PaymentController::class, 'update']);
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve']);
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject']);

    // Self-declaration routes
    Route::post('/self-declarations', [SelfDeclarationController::class, 'store']);

    // Self-declaration routes (super admin only)
    Route::get('/self-declarations', [SelfDeclarationController::class, 'index']);
    Route::get('/self-declarations/{selfDeclaration}', [SelfDeclarationController::class, 'show']);
    Route::post('/self-declarations/{selfDeclaration}/approve', [SelfDeclarationController::class, 'approve']);
    Route::post('/self-declarations/{selfDeclaration}/reject', [SelfDeclarationController::class, 'reject']);

    // About Us content (super admin only)
    Route::post('/about/convening-committee', [ConveningCommitteeMemberController::class, 'store']);
    Route::get('/about/convening-committee/{conveningCommitteeMember}', [ConveningCommitteeMemberController::class, 'show']);
    Route::put('/about/convening-committee/{conveningCommitteeMember}', [ConveningCommitteeMemberController::class, 'update']);
    Route::delete('/about/convening-committee/{conveningCommitteeMember}', [ConveningCommitteeMemberController::class, 'destroy']);

    Route::post('/about/advisory-body', [AdvisoryBodyMemberController::class, 'store']);
    Route::get('/about/advisory-body/{advisoryBodyMember}', [AdvisoryBodyMemberController::class, 'show']);
    Route::put('/about/advisory-body/{advisoryBodyMember}', [AdvisoryBodyMemberController::class, 'update']);
    Route::delete('/about/advisory-body/{advisoryBodyMember}', [AdvisoryBodyMemberController::class, 'destroy']);

    Route::post('/about/honor-board', [HonorBoardEntryController::class, 'store']);
    Route::get('/about/honor-board/{honorBoardEntry}', [HonorBoardEntryController::class, 'show']);
    Route::put('/about/honor-board/{honorBoardEntry}', [HonorBoardEntryController::class, 'update']);
    Route::delete('/about/honor-board/{honorBoardEntry}', [HonorBoardEntryController::class, 'destroy']);

    Route::post('/about/batch-representatives', [BatchRepresentativeController::class, 'store']);
    Route::get('/about/batch-representatives/{batchRepresentative}', [BatchRepresentativeController::class, 'show']);
    Route::put('/about/batch-representatives/{batchRepresentative}', [BatchRepresentativeController::class, 'update']);
    Route::delete('/about/batch-representatives/{batchRepresentative}', [BatchRepresentativeController::class, 'destroy']);

    // Event registration (member only)
    Route::post('/events/{event}/register', [EventController::class, 'register']);
    Route::delete('/events/{event}/register', [EventController::class, 'unregister']);

    // Events (super admin only)
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::delete('/events/{event}/photos/{eventPhoto}', [EventController::class, 'destroyPhoto']);

    // Downloads (super admin only)
    Route::post('/downloads', [DownloadController::class, 'store']);
    Route::get('/downloads/{download}', [DownloadController::class, 'show']);
    Route::put('/downloads/{download}', [DownloadController::class, 'update']);
    Route::delete('/downloads/{download}', [DownloadController::class, 'destroy']);

    // Scholarships (super admin only)
    Route::post('/scholarships', [ScholarshipController::class, 'store']);
    Route::get('/scholarships/{scholarship}', [ScholarshipController::class, 'show']);
    Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update']);
    Route::delete('/scholarships/{scholarship}', [ScholarshipController::class, 'destroy']);

    // Scholarship applications (super admin only)
    Route::get('/scholarship-applications', [ScholarshipApplicationController::class, 'index']);
    Route::get('/scholarship-applications/{scholarshipApplication}', [ScholarshipApplicationController::class, 'show']);
    Route::post('/scholarship-applications/{scholarshipApplication}/approve', [ScholarshipApplicationController::class, 'approve']);
    Route::post('/scholarship-applications/{scholarshipApplication}/reject', [ScholarshipApplicationController::class, 'reject']);
});
