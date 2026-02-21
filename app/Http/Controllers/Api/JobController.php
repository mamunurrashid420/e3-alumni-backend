<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobRequest;
use App\Http\Requests\Api\UpdateJobRequest;
use App\Http\Resources\Api\JobResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobController extends Controller
{
    /**
     * Display a listing of jobs (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Job::query()->orderBy('sort_order')->orderBy('id');

        if ($request->filled('status') && in_array($request->status, ['active', 'expired'], true)) {
            $query->where('status', $request->status);
        }

        $jobs = $query->get();

        return response()->json([
            'data' => JobResource::collection($jobs),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreJobRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['logo']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'job_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['logo'] = $file->storeAs('jobs', $filename, 'public');
        }

        $job = Job::create($data);

        return (new JobResource($job))->response()->setStatusCode(201);
    }

    /**
     * Display the specified job (public read).
     */
    public function showPublic(Job $job): JsonResponse
    {
        return response()->json([
            'data' => new JobResource($job),
        ]);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(Job $job): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new JobResource($job),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateJobRequest $request, Job $job): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            if ($job->logo) {
                Storage::disk('public')->delete($job->logo);
            }
            $file = $request->file('logo');
            $filename = 'job_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['logo'] = $file->storeAs('jobs', $filename, 'public');
        }

        $job->update($data);

        return response()->json([
            'data' => new JobResource($job->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(Job $job): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($job->logo) {
            Storage::disk('public')->delete($job->logo);
        }
        $job->delete();

        return response()->json(null, 204);
    }
}
