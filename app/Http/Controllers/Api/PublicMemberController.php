<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PublicMemberResource;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMemberController extends Controller
{
    /**
     * Display a paginated listing of members (public).
     * Data is sourced from users and their member profiles only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('role', UserRole::Member)
            ->whereNotNull('member_id')
            ->with(['secondaryMemberType', 'memberProfile']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(member_id) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($request->filled('primary_member_type')) {
            $query->where('primary_member_type', $request->primary_member_type);
        }

        if ($request->boolean('has_secondary_type')) {
            $query->whereNotNull('secondary_member_type_id');
        }

        if ($request->filled('secondary_member_type_id')) {
            $query->where('secondary_member_type_id', $request->secondary_member_type_id);
        }

        $members = $query->latest()->paginate($request->integer('per_page', 12));

        return PublicMemberResource::collection($members)->response();
    }
}
