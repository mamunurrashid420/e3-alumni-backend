<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreHonorBoardEntryRequest;
use App\Http\Requests\Api\UpdateHonorBoardEntryRequest;
use App\Http\Resources\Api\HonorBoardEntryResource;
use App\Models\HonorBoardEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HonorBoardEntryController extends Controller
{
    /**
     * Display a listing (public). Optional ?role=President|GeneralSecretary to filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = HonorBoardEntry::query()->orderBy('sort_order');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $entries = $query->get();

        return response()->json([
            'data' => HonorBoardEntryResource::collection($entries),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreHonorBoardEntryRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/honor-board', $filename, 'public');
        }
        $entry = HonorBoardEntry::create($data);

        return (new HonorBoardEntryResource($entry))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(HonorBoardEntry $honorBoardEntry): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new HonorBoardEntryResource($honorBoardEntry),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateHonorBoardEntryRequest $request, HonorBoardEntry $honorBoardEntry): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            if ($honorBoardEntry->photo) {
                Storage::disk('public')->delete($honorBoardEntry->photo);
            }
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/honor-board', $filename, 'public');
        }
        $honorBoardEntry->update($data);

        return response()->json([
            'data' => new HonorBoardEntryResource($honorBoardEntry->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(HonorBoardEntry $honorBoardEntry): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($honorBoardEntry->photo) {
            Storage::disk('public')->delete($honorBoardEntry->photo);
        }
        $honorBoardEntry->delete();

        return response()->json(null, 204);
    }
}
