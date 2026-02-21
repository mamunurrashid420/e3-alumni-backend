<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EventListResource;
use App\Http\Resources\Api\GalleryPhotoResource;
use App\Http\Resources\Api\JobResource;
use App\Http\Resources\Api\NewsResource;
use App\Http\Resources\Api\NoticeResource;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\GalleryPhoto;
use App\Models\Job;
use App\Models\News;
use App\Models\Notice;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    /**
     * Return all data needed to render the public homepage in a single response.
     */
    public function index(Request $request): JsonResponse
    {
        $notices = $this->getNotices($request);
        $events = $this->getUpcomingEvents();
        $galleryPhotos = $this->getGalleryPhotos();
        $jobs = $this->getJobs();
        $news = $this->getNews();
        $stats = $this->getStats();

        return response()->json([
            'notices' => ['data' => NoticeResource::collection($notices)],
            'events' => ['data' => EventListResource::collection($events)],
            'gallery_photos' => ['data' => GalleryPhotoResource::collection($galleryPhotos)],
            'jobs' => ['data' => JobResource::collection($jobs)],
            'news' => ['data' => NewsResource::collection($news)],
            'stats' => $stats,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Notice>
     */
    private function getNotices(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = Notice::query()->orderBy('sort_order')->orderBy('id');

        if (! $request->user()?->isSuperAdmin()) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Event>
     */
    private function getUpcomingEvents(): \Illuminate\Database\Eloquent\Collection
    {
        return Event::query()
            ->withCount('registrations')
            ->open()
            ->upcoming()
            ->orderBy('event_at', 'desc')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\GalleryPhoto>
     */
    private function getGalleryPhotos(): \Illuminate\Database\Eloquent\Collection
    {
        return GalleryPhoto::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Job>
     */
    private function getJobs(): \Illuminate\Database\Eloquent\Collection
    {
        return Job::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, News>
     */
    private function getNews(): \Illuminate\Database\Eloquent\Collection
    {
        $query = News::query()
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->orderBy('id');

        if (! request()->user()?->isSuperAdmin()) {
            $query->where('is_published', true);
        }

        return $query->limit(10)->get();
    }

    /**
     * @return array{members: int, events: int, photos: int, awards: int}
     */
    private function getStats(): array
    {
        $members = User::query()
            ->where('role', UserRole::Member)
            ->whereNotNull('member_id')
            ->count();

        $events = Event::query()->count();

        $eventPhotos = EventPhoto::query()->count();
        $eventCoverPhotos = Event::query()->whereNotNull('cover_photo')->count();
        $photos = $eventPhotos + $eventCoverPhotos;

        $awards = (int) config('app.homepage_awards_count', 0);

        return [
            'members' => $members,
            'events' => $events,
            'photos' => $photos,
            'awards' => $awards,
        ];
    }
}
