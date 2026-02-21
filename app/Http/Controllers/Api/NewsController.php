<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreNewsRequest;
use App\Http\Requests\Api\UpdateNewsRequest;
use App\Http\Resources\Api\NewsResource;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Display a listing of news (public: published only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = News::query()->orderByDesc('published_at')->orderBy('sort_order')->orderBy('id');

        if (! $request->user()?->isSuperAdmin()) {
            $query->where('is_published', true);
        }

        $perPage = $request->integer('per_page', 10);
        $news = $query->limit(max(1, min(100, $perPage)))->get();

        return response()->json([
            'data' => NewsResource::collection($news),
        ]);
    }

    /**
     * Store a newly created resource (super_admin only).
     */
    public function store(StoreNewsRequest $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['image']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = $data['is_published'] ?? false;
        if (empty($data['slug'] ?? null)) {
            unset($data['slug']);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'news_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['image'] = $file->storeAs('news', $filename, 'public');
        }

        $news = News::create($data);

        return (new NewsResource($news))->response()->setStatusCode(201);
    }

    /**
     * Display a single news by slug (public: published only, or super_admin sees any).
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $user = request()->user();
        if (! $user?->isSuperAdmin() && ! $news->is_published) {
            abort(404);
        }

        return response()->json([
            'data' => new NewsResource($news),
        ]);
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(News $news): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        return response()->json([
            'data' => new NewsResource($news),
        ]);
    }

    /**
     * Update the specified resource (super_admin only).
     */
    public function update(UpdateNewsRequest $request, News $news): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }
            $file = $request->file('image');
            $filename = 'news_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['image'] = $file->storeAs('news', $filename, 'public');
        }

        $news->update($data);

        return response()->json([
            'data' => new NewsResource($news->fresh()),
        ]);
    }

    /**
     * Remove the specified resource (super_admin only).
     */
    public function destroy(News $news): JsonResponse
    {
        if (! request()->user() || ! request()->user()->isSuperAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }
        $news->delete();

        return response()->json(null, 204);
    }
}
