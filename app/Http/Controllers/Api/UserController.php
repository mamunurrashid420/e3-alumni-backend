<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateMemberProfileRequest;
use App\Http\Requests\Api\UpdateMemberRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\MemberProfileResource;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Notifications\MembershipApprovedSms;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Return the authenticated user with profile (GET /user).
     */
    public function showCurrentUser(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json($this->currentUserResponse($request));
    }

    /**
     * Update the authenticated user's profile (name, email, phone).
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json($this->currentUserResponse($request));
    }

    /**
     * Update the authenticated user's member profile (address, profession, etc.).
     */
    public function updateMemberProfile(UpdateMemberProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->load('memberProfile');

        if (! $user->memberProfile) {
            return response()->json(['message' => 'Member profile not found.'], 404);
        }

        $user->memberProfile->update($request->validated());

        return response()->json($this->currentUserResponse($request));
    }

    /**
     * Build the response shape for GET /user (UserResource + profile from member_profiles).
     *
     * @return array<string, mixed>
     */
    private function currentUserResponse(Request $request): array
    {
        $user = $request->user();
        $user->load(['secondaryMemberType', 'memberProfile']);

        $userResource = new UserResource($user);
        $userData = $userResource->toArray($request);

        $userData['profile'] = $user->memberProfile
            ? (new MemberProfileResource($user->memberProfile))->toArray($request)
            : null;

        return $userData;
    }

    /**
     * Display a listing of all member users.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = User::query()->where('role', UserRole::Member)->with(['secondaryMemberType', 'memberProfile']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(member_id) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($request->has('primary_member_type')) {
            $query->where('primary_member_type', $request->primary_member_type);
        }

        $members = $query->latest()->paginate(15);

        return UserResource::collection($members)->response();
    }

    /**
     * Display the specified member user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the user is a member, not a super admin
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }

        $user->load(['secondaryMemberType', 'memberProfile']);

        return (new UserResource($user))->response();
    }

    /**
     * Update the specified member's profile (address, profession, etc.). Super admin only.
     */
    public function updateMemberProfileForMember(UpdateMemberProfileRequest $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }

        $user->load('memberProfile');

        if (! $user->memberProfile) {
            return response()->json(['message' => 'Member profile not found.'], 404);
        }

        $user->memberProfile->update($request->validated());
        $user->load(['secondaryMemberType', 'memberProfile']);

        return (new UserResource($user))->response();
    }

    /**
     * Update the specified member (name, email, phone). Super admin only.
     * Returns phone_changed: true when the stored phone was changed so admin can prompt to resend SMS.
     */
    public function update(UpdateMemberRequest $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }

        $validated = $request->validated();
        $phoneBefore = $user->phone;
        $user->update($validated);
        $phoneChanged = $phoneBefore !== $user->phone;

        $user->load('secondaryMemberType');
        $response = (new UserResource($user))->response();
        $data = $response->getData(true);
        $data['phone_changed'] = $phoneChanged;

        return response()->json($data);
    }

    /**
     * Resend credentials via SMS to the specified member.
     */
    public function resendSms(Request $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if (! $user->phone) {
            return response()->json([
                'message' => 'User does not have a phone number.',
            ], 422);
        }

        // Generate random 8-digit numerical password
        $password = (string) random_int(10000000, 99999999);

        // Optionally reset password if we want to ensure they can login with it
        $user->update([
            'password' => Hash::make($password),
        ]);

        $user->notify(new MembershipApprovedSms($password, $user->member_id));

        return response()->json([
            'message' => 'SMS sent successfully.',
        ]);
    }
}
