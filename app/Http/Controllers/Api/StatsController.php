<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Display homepage statistics (public).
     *
     * @return array{members: int, events: int, photos: int, awards: int}
     */
    public function index(): JsonResponse
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

        return response()->json([
            'members' => $members,
            'events' => $events,
            'photos' => $photos,
            'awards' => $awards,
        ]);
    }
}
