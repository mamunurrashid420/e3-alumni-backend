<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\UserRole;
use App\Notifications\MembershipApprovedSms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of all member users.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = User::query()->where('role', UserRole::Member)->with('secondaryMemberType');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(member_id) LIKE ?', ['%' . strtolower($search) . '%']);
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
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the user is a member, not a super admin
        if (!$user->isMember()) {
            abort(404, 'Member not found.');
        }

        $user->load('secondaryMemberType');

        return (new UserResource($user))->response();
    }

    /**
     * Resend credentials via SMS to the specified member.
     */
    public function resendSms(Request $request, User $user): JsonResponse
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->phone) {
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
