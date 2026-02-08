<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RenewMembershipRequest;
use App\Http\Requests\Api\UpdateMemberProfileRequest;
use App\Http\Requests\Api\UpdateMemberRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\MemberProfileResource;
use App\Http\Resources\Api\UserResource;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\MembershipApprovedSms;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * Accepts optional photo file (multipart).
     */
    public function updateMemberProfile(UpdateMemberProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->load('memberProfile');

        if (! $user->memberProfile) {
            return response()->json(['message' => 'Member profile not found.'], 404);
        }

        $data = $request->validated();
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $profile = $user->memberProfile;
            if ($profile->photo) {
                Storage::disk('public')->delete($profile->photo);
            }
            $file = $request->file('photo');
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = 'photo_'.Str::random(20).'.'.$ext;
            $path = $file->storeAs(MemberProfile::STORAGE_DIR.'/'.$user->id, $filename, 'public');
            $data['photo'] = $path;
        }

        $user->memberProfile->update($data);

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
        $user->load([
            'secondaryMemberType',
            'memberProfile',
            'selfDeclarations' => fn ($q) => $q->latest()->limit(1),
        ]);

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

        $perPage = min(10000, max(1, $request->integer('per_page', 15)));
        $members = $query->latest()->paginate($perPage);

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

    /**
     * Renew membership by extending the expiry date. Super admin only.
     * Only applicable to GENERAL and ASSOCIATE members (not LIFETIME).
     */
    public function renewMembership(RenewMembershipRequest $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }
        if ($user->primary_member_type === \App\PrimaryMemberType::Lifetime) {
            return response()->json([
                'message' => 'Lifetime membership does not expire and cannot be renewed.',
            ], 422);
        }

        $years = (int) $request->validated('years');
        $newExpiresAt = $user->extendMembershipExpiryByYears($years);
        $user->update([
            'membership_expires_at' => $newExpiresAt,
            'membership_renewed_at' => now(),
        ]);
        $user->load(['secondaryMemberType', 'memberProfile']);

        return (new UserResource($user))->response();
    }

    /**
     * Disable the specified member. Super admin only. Disabled users cannot log in.
     */
    public function disable(Request $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }

        $user->update(['disabled_at' => now()]);
        $user->tokens()->delete();
        $user->load(['secondaryMemberType', 'memberProfile']);

        return (new UserResource($user))->response();
    }

    /**
     * Re-enable the specified member. Super admin only.
     */
    public function enable(Request $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }

        $user->update(['disabled_at' => null]);
        $user->load(['secondaryMemberType', 'memberProfile']);

        return (new UserResource($user))->response();
    }

    /**
     * Delete the specified member. Super admin only. Cannot delete self.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }
        if (! $user->isMember()) {
            abort(404, 'Member not found.');
        }

        $user->delete();

        return response()->json(['message' => 'Member deleted successfully.'], 200);
    }
}
