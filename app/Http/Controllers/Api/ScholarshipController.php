<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScholarshipRequest;
use App\Http\Requests\Api\UpdateScholarshipRequest;
use App\Http\Resources\Api\ScholarshipResource;
use App\Models\Scholarship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    /**
     * Display a listing. Public: active only. Super admin: all (optional filter).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Scholarship::query()->orderBy('sort_order')->orderBy('id');

        if ($request->user()?->isSuperAdmin()) {
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            $scholarships = $query->get();
        } else {
            $query->where('is_active', true);
            $scholarships = $query->get();
        }

        return response()->json([
            'data' => ScholarshipResource::collection($scholarships),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreScholarshipRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $scholarship = Scholarship::create($request->validated());

        return (new ScholarshipResource($scholarship))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(Request $request, Scholarship $scholarship): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new ScholarshipResource($scholarship),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateScholarshipRequest $request, Scholarship $scholarship): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $scholarship->update($request->validated());

        return response()->json([
            'data' => new ScholarshipResource($scholarship->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(Request $request, Scholarship $scholarship): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $scholarship->delete();

        return response()->json(null, 204);
    }
}
