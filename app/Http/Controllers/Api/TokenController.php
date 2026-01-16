<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    /**
     * Create a new API token for the authenticated user.
     */
    public function store(CreateTokenRequest $request): JsonResponse
    {
        $abilities = $request->abilities ?? ['*'];

        $token = $request->user()->createToken(
            $request->token_name,
            $abilities
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_name' => $request->token_name,
            'abilities' => $abilities,
        ], 201);
    }

    /**
     * List all tokens for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ];
        });

        return response()->json(['tokens' => $tokens]);
    }

    /**
     * Delete a specific token.
     */
    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if (! $token) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        $token->delete();

        return response()->json(['message' => 'Token deleted successfully']);
    }

    /**
     * Delete all tokens for the authenticated user.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens deleted successfully']);
    }
}
