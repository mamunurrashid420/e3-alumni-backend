<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDownloadRequest;
use App\Http\Requests\Api\UpdateDownloadRequest;
use App\Http\Resources\Api\DownloadResource;
use App\Models\Download;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    /**
     * Display a listing (public).
     */
    public function index(): JsonResponse
    {
        $downloads = Download::query()->orderBy('sort_order')->get();

        return response()->json([
            'data' => DownloadResource::collection($downloads),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreDownloadRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['file']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['file_path'] = $file->storeAs('downloads', $filename, 'public');
        }

        $download = Download::create($data);

        return (new DownloadResource($download))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(Download $download): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new DownloadResource($download),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateDownloadRequest $request, Download $download): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['file']);

        if ($request->hasFile('file')) {
            if ($download->file_path) {
                Storage::disk('public')->delete($download->file_path);
            }
            $file = $request->file('file');
            $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['file_path'] = $file->storeAs('downloads', $filename, 'public');
        }

        $download->update($data);

        return response()->json([
            'data' => new DownloadResource($download->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(Download $download): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($download->file_path) {
            Storage::disk('public')->delete($download->file_path);
        }
        $download->delete();

        return response()->json(null, 204);
    }
}
