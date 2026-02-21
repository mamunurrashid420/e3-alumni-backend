<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGalleryPhotoRequest;
use App\Http\Requests\Api\UpdateGalleryPhotoRequest;
use App\Http\Resources\Api\GalleryPhotoResource;
use App\Models\GalleryPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class GalleryPhotoController extends Controller
{
    /**
     * Display a listing of gallery photos (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = GalleryPhoto::query()->orderBy('sort_order')->orderBy('id');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $photos = $query->get();

        return response()->json([
            'data' => GalleryPhotoResource::collection($photos),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreGalleryPhotoRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        try {
            $data = $request->validated();
            unset($data['image']);
            $data['sort_order'] = $data['sort_order'] ?? 0;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = 'gallery_'.Str::random(20).'.'.$file->getClientOriginalExtension();
                $data['image'] = $file->storeAs('gallery', $filename, 'public');
            }

            $photo = GalleryPhoto::create($data);

            return (new GalleryPhotoResource($photo))->response()->setStatusCode(201);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to save the gallery photo. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(GalleryPhoto $galleryPhoto): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new GalleryPhotoResource($galleryPhoto),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateGalleryPhotoRequest $request, GalleryPhoto $galleryPhoto): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        try {
            $data = $request->validated();
            unset($data['image']);

            if ($request->hasFile('image')) {
                if ($galleryPhoto->image) {
                    Storage::disk('public')->delete($galleryPhoto->image);
                }
                $file = $request->file('image');
                $filename = 'gallery_'.Str::random(20).'.'.$file->getClientOriginalExtension();
                $data['image'] = $file->storeAs('gallery', $filename, 'public');
            }

            $galleryPhoto->update($data);

            return response()->json([
                'data' => new GalleryPhotoResource($galleryPhoto->fresh()),
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to update the gallery photo. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(GalleryPhoto $galleryPhoto): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($galleryPhoto->image) {
            Storage::disk('public')->delete($galleryPhoto->image);
        }
        $galleryPhoto->delete();

        return response()->json(null, 204);
    }
}
