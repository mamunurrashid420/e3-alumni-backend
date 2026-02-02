<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdvisoryBodyMemberRequest;
use App\Http\Requests\Api\UpdateAdvisoryBodyMemberRequest;
use App\Http\Resources\Api\AdvisoryBodyMemberResource;
use App\Models\AdvisoryBodyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdvisoryBodyMemberController extends Controller
{
    /**
     * Display a listing (public).
     */
    public function index(): JsonResponse
    {
        $members = AdvisoryBodyMember::query()->orderBy('sort_order')->get();

        return response()->json([
            'data' => AdvisoryBodyMemberResource::collection($members),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreAdvisoryBodyMemberRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/advisory-body', $filename, 'public');
        }
        $member = AdvisoryBodyMember::create($data);

        return (new AdvisoryBodyMemberResource($member))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(AdvisoryBodyMember $advisoryBodyMember): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new AdvisoryBodyMemberResource($advisoryBodyMember),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateAdvisoryBodyMemberRequest $request, AdvisoryBodyMember $advisoryBodyMember): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            if ($advisoryBodyMember->photo) {
                Storage::disk('public')->delete($advisoryBodyMember->photo);
            }
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/advisory-body', $filename, 'public');
        }
        $advisoryBodyMember->update($data);

        return response()->json([
            'data' => new AdvisoryBodyMemberResource($advisoryBodyMember->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(AdvisoryBodyMember $advisoryBodyMember): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($advisoryBodyMember->photo) {
            Storage::disk('public')->delete($advisoryBodyMember->photo);
        }
        $advisoryBodyMember->delete();

        return response()->json(null, 204);
    }
}
