<?php

use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Job;
use App\Models\News;
use App\Models\Notice;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns combined homepage data publicly', function () {
    Storage::fake('public');
    User::factory()->count(2)->create([
        'role' => UserRole::Member,
        'member_id' => fn () => 'M'.fake()->unique()->numberBetween(1000, 9999),
    ]);
    Notice::factory()->create(['title' => 'Active Notice', 'is_active' => true]);
    Event::factory()->open()->create([
        'title' => 'Upcoming Event',
        'event_at' => now()->addDays(5),
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDays(2),
    ]);
    GalleryPhoto::factory()->count(2)->create();
    Job::factory()->count(1)->create();
    News::factory()->published()->count(1)->create();

    $response = $this->getJson('/api/homepage');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'notices' => ['data' => [['id', 'title', 'body', 'is_active', 'sort_order']]],
            'events' => ['data' => [['id', 'title', 'event_at', 'status', 'registration_count']]],
            'gallery_photos' => ['data' => [['id', 'url', 'category', 'sort_order']]],
            'jobs' => ['data' => [['id', 'title', 'status']]],
            'news' => ['data' => [['id', 'slug', 'title', 'published_at']]],
            'stats' => ['members', 'events', 'photos', 'awards'],
        ])
        ->assertJsonCount(1, 'notices.data')
        ->assertJsonPath('notices.data.0.title', 'Active Notice')
        ->assertJsonCount(1, 'events.data')
        ->assertJsonPath('events.data.0.title', 'Upcoming Event')
        ->assertJsonCount(2, 'gallery_photos.data')
        ->assertJsonCount(1, 'jobs.data')
        ->assertJsonCount(1, 'news.data')
        ->assertJsonPath('stats.members', 2)
        ->assertJsonPath('stats.events', 1);
});

it('returns only active notices and published news on homepage when unauthenticated', function () {
    Notice::factory()->create(['is_active' => true]);
    Notice::factory()->create(['is_active' => false]);
    News::factory()->published()->create();
    News::factory()->create(['is_published' => false]);

    $response = $this->getJson('/api/homepage');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'notices.data')
        ->assertJsonCount(1, 'news.data');
});
