<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// --- Public index ---

it('returns events list publicly', function () {
    Event::factory()->open()->count(2)->create();

    $response = $this->getJson('/api/events');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'title', 'location', 'event_at', 'registration_opens_at', 'registration_closes_at', 'status', 'cover_photo', 'registration_count']]])
        ->assertJsonCount(2, 'data');
});

it('filters events by status open', function () {
    Event::factory()->open()->create(['title' => 'Open Event']);
    Event::factory()->closed()->create(['title' => 'Closed Event']);

    $response = $this->getJson('/api/events?status=open');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Open Event');
});

it('filters events by status closed', function () {
    Event::factory()->open()->create(['title' => 'Open Event']);
    Event::factory()->closed()->create(['title' => 'Closed Event']);

    $response = $this->getJson('/api/events?status=closed');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Closed Event');
});

it('filters upcoming open events by registration_closes_at not yet passed', function () {
    Event::factory()->open()->create([
        'title' => 'Registration Open',
        'event_at' => now()->addDays(5),
        'registration_opens_at' => now()->subDays(2),
        'registration_closes_at' => now()->addDays(2),
    ]);
    Event::factory()->open()->create([
        'title' => 'Registration Closed',
        'event_at' => now()->addDays(5),
        'registration_opens_at' => now()->subDays(5),
        'registration_closes_at' => now()->subDay(),
    ]);

    $response = $this->getJson('/api/events?status=open&upcoming=true');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Registration Open');
});

// --- Public show ---

it('returns open event publicly', function () {
    $event = Event::factory()->open()->create(['title' => 'Meetup']);

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.title', 'Meetup')
        ->assertJsonPath('data.status', EventStatus::Open->value);
});

it('returns 404 for draft event when not authenticated', function () {
    $event = Event::factory()->draft()->create();

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertNotFound();
});

it('returns draft event when super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $event = Event::factory()->draft()->create(['title' => 'Draft Event']);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/events/{$event->id}");

    $response->assertSuccessful()->assertJsonPath('data.title', 'Draft Event');
});

// --- Register / Unregister ---

it('registers member for open event', function () {
    Storage::fake('public');
    $member = User::factory()->member()->create(['member_id' => 'G-2000-0001']);
    $event = Event::factory()->open()->create();

    $response = $this->actingAs($member, 'sanctum')
        ->postJson("/api/events/{$event->id}/register");

    $response->assertCreated()->assertJson(['message' => 'Registered successfully.']);
    $this->assertDatabaseHas('event_registrations', ['event_id' => $event->id, 'user_id' => $member->id]);
});

it('returns 401 when unauthenticated user tries to register', function () {
    $event = Event::factory()->open()->create();

    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertUnauthorized();
});

it('registers guest for open event without auth', function () {
    $event = Event::factory()->open()->create();

    $response = $this->postJson("/api/events/{$event->id}/register-guest", [
        'name' => 'Guest User',
        'phone' => '+8801712345678',
        'address' => '123 Street, Dhaka',
        'ssc_jsc' => 'SSC 2010',
    ]);

    $response->assertCreated()->assertJson(['message' => 'Registered successfully.']);
    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $event->id,
        'user_id' => null,
        'name' => 'Guest User',
        'phone' => '+8801712345678',
        'address' => '123 Street, Dhaka',
        'ssc_jsc' => 'SSC 2010',
    ]);
});

it('registers guest with optional ssc_jsc', function () {
    $event = Event::factory()->open()->create();

    $response = $this->postJson("/api/events/{$event->id}/register-guest", [
        'name' => 'Guest User',
        'phone' => '+8801712345678',
        'address' => '123 Street, Dhaka',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $event->id,
        'user_id' => null,
        'name' => 'Guest User',
        'ssc_jsc' => null,
    ]);
});

it('returns 422 when guest registration and event is closed', function () {
    $event = Event::factory()->closed()->create();

    $response = $this->postJson("/api/events/{$event->id}/register-guest", [
        'name' => 'Guest User',
        'phone' => '+8801712345678',
        'address' => '123 Street, Dhaka',
    ]);

    $response->assertUnprocessable();
});

it('returns 422 when guest registration missing required fields', function () {
    $event = Event::factory()->open()->create();

    $response = $this->postJson("/api/events/{$event->id}/register-guest", [
        'name' => 'Guest User',
    ]);

    $response->assertUnprocessable();
});

it('forbids registration when user has no member_id', function () {
    $user = User::factory()->member()->create(['member_id' => null]);
    $event = Event::factory()->open()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/events/{$event->id}/register");

    $response->assertForbidden();
});

it('returns 422 when event is not open for registration', function () {
    $member = User::factory()->member()->create(['member_id' => 'G-2000-0001']);
    $event = Event::factory()->closed()->create();

    $response = $this->actingAs($member, 'sanctum')
        ->postJson("/api/events/{$event->id}/register");

    $response->assertUnprocessable();
});

it('returns 422 when registration period has ended', function () {
    $member = User::factory()->member()->create(['member_id' => 'G-2000-0001']);
    $event = Event::factory()->open()->create([
        'event_at' => now()->addDays(5),
        'registration_opens_at' => now()->subDays(10),
        'registration_closes_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->postJson("/api/events/{$event->id}/register");

    $response->assertUnprocessable()->assertJsonFragment(['message' => 'Registration period has ended for this event.']);
});

it('returns 422 when guest registration and registration period has ended', function () {
    $event = Event::factory()->open()->create([
        'event_at' => now()->addDays(5),
        'registration_opens_at' => now()->subDays(10),
        'registration_closes_at' => now()->subDay(),
    ]);

    $response = $this->postJson("/api/events/{$event->id}/register-guest", [
        'name' => 'Guest User',
        'phone' => '+8801712345678',
        'address' => '123 Street, Dhaka',
    ]);

    $response->assertUnprocessable()->assertJsonFragment(['message' => 'Registration period has ended for this event.']);
});

it('returns 422 when already registered', function () {
    $member = User::factory()->member()->create(['member_id' => 'G-2000-0001']);
    $event = Event::factory()->open()->create();
    EventRegistration::create(['event_id' => $event->id, 'user_id' => $member->id, 'registered_at' => now()]);

    $response = $this->actingAs($member, 'sanctum')
        ->postJson("/api/events/{$event->id}/register");

    $response->assertUnprocessable();
});

it('unregisters member from event', function () {
    $member = User::factory()->member()->create(['member_id' => 'G-2000-0001']);
    $event = Event::factory()->open()->create();
    EventRegistration::create(['event_id' => $event->id, 'user_id' => $member->id, 'registered_at' => now()]);

    $response = $this->actingAs($member, 'sanctum')
        ->deleteJson("/api/events/{$event->id}/register");

    $response->assertNoContent();
    $this->assertDatabaseMissing('event_registrations', ['event_id' => $event->id, 'user_id' => $member->id]);
});

// --- Super admin: store ---

it('stores event as super admin with cover photo', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $file = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->post('/api/events', [
            'title' => 'Annual Meetup',
            'description' => 'Yearly gathering',
            'location' => 'Dhaka',
            'event_at' => now()->addDays(5)->toIso8601String(),
            'registration_opens_at' => now()->addDay()->toIso8601String(),
            'registration_closes_at' => now()->addDays(4)->toIso8601String(),
            'status' => EventStatus::Open->value,
            'cover_photo' => $file,
        ], ['Accept' => 'application/json']);

    $response->assertCreated()->assertJsonPath('data.title', 'Annual Meetup');
    $this->assertDatabaseHas('events', ['title' => 'Annual Meetup']);
    $event = Event::first();
    expect($event->cover_photo)->not->toBeNull();
    Storage::disk('public')->assertExists($event->cover_photo);
});

it('forbids storing event as non super admin', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', [
            'title' => 'Test',
            'event_at' => now()->addDays(2)->toIso8601String(),
            'registration_opens_at' => now()->subDay()->toIso8601String(),
            'registration_closes_at' => now()->addDay()->toIso8601String(),
            'status' => EventStatus::Open->value,
        ]);

    $response->assertForbidden();
});

it('validates event store request', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/events', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['title', 'event_at', 'registration_opens_at', 'registration_closes_at', 'status']);
});

// --- Super admin: update (close with photos) ---

it('updates event and can close with photos', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $event = Event::factory()->open()->create(['title' => 'Old Title']);
    $file1 = UploadedFile::fake()->create('photo1.jpg', 100, 'image/jpeg');
    $file2 = UploadedFile::fake()->create('photo2.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->post("/api/events/{$event->id}", [
            '_method' => 'PUT',
            'title' => 'Old Title',
            'description' => $event->description,
            'location' => $event->location,
            'event_at' => $event->event_at->toIso8601String(),
            'registration_opens_at' => $event->registration_opens_at->toIso8601String(),
            'registration_closes_at' => $event->registration_closes_at->toIso8601String(),
            'status' => EventStatus::Closed->value,
            'photos' => [$file1, $file2],
        ], ['Accept' => 'application/json']);

    $response->assertSuccessful()->assertJsonPath('data.status', EventStatus::Closed->value);
    $event->refresh();
    expect($event->status)->toBe(EventStatus::Closed);
    expect($event->photos)->toHaveCount(2);
});

it('forbids updating event as non super admin', function () {
    $event = Event::factory()->open()->create();

    $response = $this->actingAs(User::factory()->member()->create(), 'sanctum')
        ->putJson("/api/events/{$event->id}", [
            'title' => 'Updated',
            'event_at' => $event->event_at->toIso8601String(),
            'registration_opens_at' => $event->registration_opens_at->toIso8601String(),
            'registration_closes_at' => $event->registration_closes_at->toIso8601String(),
            'status' => $event->status->value,
        ]);

    $response->assertForbidden();
});

// --- Super admin: destroy ---

it('deletes event as super admin', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $event = Event::factory()->open()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/events/{$event->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

it('forbids deleting event as non super admin', function () {
    $event = Event::factory()->open()->create();

    $response = $this->actingAs(User::factory()->member()->create(), 'sanctum')
        ->deleteJson("/api/events/{$event->id}");

    $response->assertForbidden();
});

it('deletes event gallery photo as super admin', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $event = Event::factory()->closed()->create();
    $photo1 = $event->photos()->create(['path' => 'events/1/gallery/photo1.jpg', 'sort_order' => 0]);
    $event->photos()->create(['path' => 'events/1/gallery/photo2.jpg', 'sort_order' => 1]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/events/{$event->id}/photos/{$photo1->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('event_photos', ['id' => $photo1->id]);
    expect($event->photos()->count())->toBe(1);
});

it('forbids deleting event photo as non super admin', function () {
    $event = Event::factory()->closed()->create();
    $photo = $event->photos()->create(['path' => 'events/1/gallery/photo1.jpg', 'sort_order' => 0]);

    $response = $this->actingAs(User::factory()->member()->create(), 'sanctum')
        ->deleteJson("/api/events/{$event->id}/photos/{$photo->id}");

    $response->assertForbidden();
});

// --- Registrations list (super admin) ---

it('returns event registrations as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $event = Event::factory()->open()->create();
    $member = User::factory()->member()->create(['member_id' => 'G-2000-0001', 'name' => 'Jane']);
    EventRegistration::create(['event_id' => $event->id, 'user_id' => $member->id, 'registered_at' => now()]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/events/{$event->id}/registrations");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.name', 'Jane');
});

it('forbids listing registrations as non super admin', function () {
    $event = Event::factory()->open()->create();

    $response = $this->actingAs(User::factory()->member()->create(), 'sanctum')
        ->getJson("/api/events/{$event->id}/registrations");

    $response->assertForbidden();
});
