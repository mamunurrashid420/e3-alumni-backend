<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBatchRepresentativeRequest;
use App\Http\Requests\Api\UpdateBatchRepresentativeRequest;
use App\Http\Resources\Api\BatchRepresentativeResource;
use App\Models\BatchRepresentative;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BatchRepresentativeController extends Controller
{
    /**
     * Display a listing (public).
     */
    public function index(): JsonResponse
    {
        $representatives = BatchRepresentative::query()->orderBy('sort_order')->get();

        return response()->json([
            'data' => BatchRepresentativeResource::collection($representatives),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreBatchRepresentativeRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/batch-representatives', $filename, 'public');
        }
        $representative = BatchRepresentative::create($data);

        return (new BatchRepresentativeResource($representative))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(BatchRepresentative $batchRepresentative): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new BatchRepresentativeResource($batchRepresentative),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateBatchRepresentativeRequest $request, BatchRepresentative $batchRepresentative): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            if ($batchRepresentative->photo) {
                Storage::disk('public')->delete($batchRepresentative->photo);
            }
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['photo'] = $file->storeAs('about-us/batch-representatives', $filename, 'public');
        }
        $batchRepresentative->update($data);

        return response()->json([
            'data' => new BatchRepresentativeResource($batchRepresentative->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(BatchRepresentative $batchRepresentative): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($batchRepresentative->photo) {
            Storage::disk('public')->delete($batchRepresentative->photo);
        }
        $batchRepresentative->delete();

        return response()->json(null, 204);
    }
}
