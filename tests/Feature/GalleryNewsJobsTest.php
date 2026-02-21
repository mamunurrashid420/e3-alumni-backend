<?php

use App\Models\GalleryPhoto;
use App\Models\Job;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// --- Gallery (public index) ---

it('returns gallery photos list publicly', function () {
    Storage::fake('public');
    GalleryPhoto::factory()->count(2)->create();

    $response = $this->getJson('/api/gallery-photos');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'url', 'category', 'sort_order']]])
        ->assertJsonCount(2, 'data');
});

it('filters gallery photos by category', function () {
    GalleryPhoto::factory()->create(['category' => 'Event']);
    GalleryPhoto::factory()->create(['category' => 'Old Memories']);

    $response = $this->getJson('/api/gallery-photos?category=Event');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.category', 'Event');
});

// --- News (public index, published only when unauthenticated) ---

it('returns only published news publicly when unauthenticated', function () {
    News::factory()->published()->create(['title' => 'Published']);
    News::factory()->create(['title' => 'Draft', 'is_published' => false]);

    $response = $this->getJson('/api/news');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Published');
});

it('returns all news when authenticated as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    News::factory()->published()->create(['title' => 'Published']);
    News::factory()->create(['title' => 'Draft', 'is_published' => false]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/news', ['Accept' => 'application/json']);

    $response->assertSuccessful()->assertJsonCount(2, 'data');
});

it('returns news by slug with slug in response', function () {
    $news = News::factory()->published()->create(['title' => 'Test News', 'slug' => 'test-news']);

    $response = $this->getJson('/api/news');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.slug', 'test-news');
});

it('returns single published news by slug publicly', function () {
    $news = News::factory()->published()->create(['title' => 'Hello World', 'slug' => 'hello-world']);

    $response = $this->getJson('/api/news/slug/hello-world');

    $response->assertSuccessful()
        ->assertJsonPath('data.slug', 'hello-world')
        ->assertJsonPath('data.title', 'Hello World');
});

it('returns 404 for unpublished news by slug when unauthenticated', function () {
    News::factory()->create(['title' => 'Draft', 'slug' => 'draft-news', 'is_published' => false]);

    $response = $this->getJson('/api/news/slug/draft-news');

    $response->assertNotFound();
});

it('returns draft news by slug when super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    News::factory()->create(['title' => 'Draft', 'slug' => 'draft-news', 'is_published' => false]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/news/slug/draft-news', ['Accept' => 'application/json']);

    $response->assertSuccessful()->assertJsonPath('data.slug', 'draft-news');
});

// --- Jobs (public index) ---

it('returns jobs list publicly', function () {
    Job::factory()->count(2)->create();

    $response = $this->getJson('/api/jobs');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'title', 'description', 'company_name', 'logo', 'status', 'application_url', 'closes_at']]])
        ->assertJsonCount(2, 'data');
});

it('filters jobs by status', function () {
    Job::factory()->create(['title' => 'Active Job', 'status' => \App\Enums\JobStatus::Active]);
    Job::factory()->expired()->create(['title' => 'Expired Job']);

    $response = $this->getJson('/api/jobs?status=active');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Active Job');
});
