<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConveningCommitteeMemberRequest;
use App\Http\Requests\Api\UpdateConveningCommitteeMemberRequest;
use App\Http\Resources\Api\ConveningCommitteeMemberResource;
use App\Models\ConveningCommitteeMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConveningCommitteeMemberController extends Controller
{
    /**
     * Display a listing (public).
     */
    public function index(): JsonResponse
    {
        $members = ConveningCommitteeMember::query()->orderBy('sort_order')->get();

        return response()->json([
            'data' => ConveningCommitteeMemberResource::collection($members),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreConveningCommitteeMemberRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/convening-committee', $filename, 'public');
        }
        $member = ConveningCommitteeMember::create($data);

        return (new ConveningCommitteeMemberResource($member))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(ConveningCommitteeMember $conveningCommitteeMember): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new ConveningCommitteeMemberResource($conveningCommitteeMember),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateConveningCommitteeMemberRequest $request, ConveningCommitteeMember $conveningCommitteeMember): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            if ($conveningCommitteeMember->photo) {
                Storage::disk('public')->delete($conveningCommitteeMember->photo);
            }
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/convening-committee', $filename, 'public');
        }
        $conveningCommitteeMember->update($data);

        return response()->json([
            'data' => new ConveningCommitteeMemberResource($conveningCommitteeMember->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(ConveningCommitteeMember $conveningCommitteeMember): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($conveningCommitteeMember->photo) {
            Storage::disk('public')->delete($conveningCommitteeMember->photo);
        }
        $conveningCommitteeMember->delete();

        return response()->json(null, 204);
    }
}
