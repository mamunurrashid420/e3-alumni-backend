<?php

namespace App\Http\Controllers\Api;

use App\Enums\SelfDeclarationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSelfDeclarationRequest;
use App\Http\Resources\Api\SelfDeclarationResource;
use App\Models\SelfDeclaration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SelfDeclarationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = SelfDeclaration::query()->with(['user', 'secondaryMemberType', 'approvedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $selfDeclarations = $query->latest()->paginate(15);

        return SelfDeclarationResource::collection($selfDeclarations)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSelfDeclarationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Check if user already has a secondary member type
        if ($user->secondary_member_type_id !== null) {
            return response()->json([
                'message' => 'You already have a secondary member type assigned.',
            ], 422);
        }

        // Check if user already has a pending self-declaration
        $existingDeclaration = SelfDeclaration::where('user_id', $user->id)
            ->where('status', SelfDeclarationStatus::Pending)
            ->first();

        if ($existingDeclaration) {
            return response()->json([
                'message' => 'You already have a pending self-declaration.',
            ], 422);
        }

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['status'] = SelfDeclarationStatus::Pending;

        // Handle signature file upload
        if ($request->hasFile('signature_file')) {
            $file = $request->file('signature_file');
            $filename = 'signature_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('self-declarations', $filename, 'public');
            $data['signature_file'] = $path;
        }

        $selfDeclaration = SelfDeclaration::create($data);
        $selfDeclaration->load(['user', 'secondaryMemberType']);

        return (new SelfDeclarationResource($selfDeclaration))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, SelfDeclaration $selfDeclaration): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $selfDeclaration->load(['user', 'secondaryMemberType', 'approvedBy']);

        return (new SelfDeclarationResource($selfDeclaration))->response();
    }

    /**
     * Approve the self-declaration.
     */
    public function approve(Request $request, SelfDeclaration $selfDeclaration): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($selfDeclaration->status !== SelfDeclarationStatus::Pending) {
            return response()->json([
                'message' => 'Self-declaration is not pending approval.',
            ], 422);
        }

        // Update user's secondary_member_type_id
        $selfDeclaration->user->update([
            'secondary_member_type_id' => $selfDeclaration->secondary_member_type_id,
        ]);

        // Update self-declaration status
        $selfDeclaration->update([
            'status' => SelfDeclarationStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $selfDeclaration->load(['user', 'secondaryMemberType', 'approvedBy']);

        return response()->json([
            'message' => 'Self-declaration approved successfully. Secondary member type assigned.',
            'self_declaration' => new SelfDeclarationResource($selfDeclaration),
        ]);
    }

    /**
     * Reject the self-declaration.
     */
    public function reject(Request $request, SelfDeclaration $selfDeclaration): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($selfDeclaration->status !== SelfDeclarationStatus::Pending) {
            return response()->json([
                'message' => 'Self-declaration is not pending approval.',
            ], 422);
        }

        $selfDeclaration->update([
            'status' => SelfDeclarationStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_reason' => $request->input('rejected_reason'),
        ]);

        $selfDeclaration->load(['user', 'secondaryMemberType', 'approvedBy']);

        return response()->json([
            'message' => 'Self-declaration rejected successfully.',
            'self_declaration' => new SelfDeclarationResource($selfDeclaration),
        ]);
    }
}
