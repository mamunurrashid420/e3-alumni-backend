<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreNoticeRequest;
use App\Http\Requests\Api\UpdateNoticeRequest;
use App\Http\Resources\Api\NoticeResource;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * Display a listing of notices (public: active only; super_admin: all).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notice::query()->orderBy('sort_order')->orderBy('id');

        if (! $request->user()?->isSuperAdmin()) {
            $query->where('is_active', true);
        }

        $notices = $query->get();

        return response()->json([
            'data' => NoticeResource::collection($notices),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreNoticeRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $notice = Notice::create($data);

        return (new NoticeResource($notice))->response()->setStatusCode(201);
    }

    /**
     * Display the specified notice (public: active only; super_admin: any).
     */
    public function show(Notice $notice): JsonResponse
    {
        $user = request()->user();
        if (! $user?->isSuperAdmin() && ! $notice->is_active) {
            abort(404);
        }

        return response()->json([
            'data' => new NoticeResource($notice),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateNoticeRequest $request, Notice $notice): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        $notice->update($data);

        return response()->json([
            'data' => new NoticeResource($notice->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(Notice $notice): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $notice->delete();

        return response()->json(null, 204);
    }
}
