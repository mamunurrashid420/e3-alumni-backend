<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => UserRole::Member,
            'primary_member_type' => $request->has('primary_member_type')
                ? PrimaryMemberType::from($request->primary_member_type)
                : null,
            'secondary_member_type_id' => $request->secondary_member_type_id ?? null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login and issue a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $emailOrPhone = $request->email_or_phone;

        // Determine if input is email (contains @) or phone
        $isEmail = str_contains($emailOrPhone, '@');

        // Find user by email or phone
        $user = $isEmail
            ? User::where('email', $emailOrPhone)->first()
            : User::where('phone', $emailOrPhone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email_or_phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->load('secondaryMemberType');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
