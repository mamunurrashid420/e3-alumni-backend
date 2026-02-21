<?php

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only active notices publicly when unauthenticated', function () {
    Notice::factory()->create(['title' => 'Active Notice', 'is_active' => true]);
    Notice::factory()->create(['title' => 'Inactive Notice', 'is_active' => false]);

    $response = $this->getJson('/api/notices');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active Notice');
});

it('returns all notices when authenticated as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    Notice::factory()->create(['title' => 'Active', 'is_active' => true]);
    Notice::factory()->create(['title' => 'Inactive', 'is_active' => false]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/notices', ['Accept' => 'application/json']);

    $response->assertSuccessful()->assertJsonCount(2, 'data');
});

it('returns single active notice by id publicly', function () {
    $notice = Notice::factory()->create(['title' => 'Important Notice', 'body' => 'Full text.', 'is_active' => true]);

    $response = $this->getJson('/api/notices/'.$notice->id);

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $notice->id)
        ->assertJsonPath('data.title', 'Important Notice')
        ->assertJsonPath('data.body', 'Full text.');
});

it('returns 404 for inactive notice when unauthenticated', function () {
    $notice = Notice::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/notices/'.$notice->id);

    $response->assertNotFound();
});

it('returns inactive notice when super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $notice = Notice::factory()->create(['title' => 'Draft Notice', 'is_active' => false]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/notices/'.$notice->id, ['Accept' => 'application/json']);

    $response->assertSuccessful()->assertJsonPath('data.title', 'Draft Notice');
});

it('allows super admin to create notice', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')->postJson('/api/notices', [
        'title' => 'New Notice',
        'body' => 'Notice body content.',
        'is_active' => true,
        'sort_order' => 1,
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'New Notice')
        ->assertJsonPath('data.body', 'Notice body content.')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.sort_order', 1);

    $this->assertDatabaseHas('notices', ['title' => 'New Notice']);
});

it('forbids non super admin from creating notice', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/notices', [
        'title' => 'New Notice',
        'body' => 'Body',
    ], ['Accept' => 'application/json']);

    $response->assertForbidden();
});

it('allows super admin to update notice', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $notice = Notice::factory()->create(['title' => 'Old Title']);

    $response = $this->actingAs($superAdmin, 'sanctum')->putJson('/api/notices/'.$notice->id, [
        'title' => 'Updated Title',
        'body' => 'Updated body.',
    ], ['Accept' => 'application/json']);

    $response->assertSuccessful()->assertJsonPath('data.title', 'Updated Title');
    $notice->refresh();
    expect($notice->title)->toBe('Updated Title');
});

it('allows super admin to delete notice', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $notice = Notice::factory()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson('/api/notices/'.$notice->id, [], ['Accept' => 'application/json']);

    $response->assertNoContent();
    $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
});
