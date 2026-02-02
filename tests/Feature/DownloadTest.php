<?php

use App\Models\Download;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns downloads list publicly', function () {
    Storage::fake('public');
    Download::factory()->count(2)->create();

    $response = $this->getJson('/api/downloads');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'title', 'description', 'file_url', 'sort_order', 'created_at']]])
        ->assertJsonCount(2, 'data');
});

it('stores download as super admin with file', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->post('/api/downloads', [
            'title' => 'Test Download',
            'description' => 'Optional description',
            'file' => $file,
            'sort_order' => 1,
        ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Test Download')
        ->assertJsonPath('data.description', 'Optional description');
    $this->assertDatabaseHas('downloads', ['title' => 'Test Download']);
    $download = Download::first();
    expect($download->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($download->file_path);
});

it('returns 401 when unauthenticated user tries to store download', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->create('doc.pdf', 100);

    $response = $this->post('/api/downloads', [
        'title' => 'Test',
        'file' => $file,
    ], ['Accept' => 'application/json']);

    $response->assertUnauthorized();
});

it('forbids storing download as non super admin', function () {
    $user = User::factory()->member()->create();
    $file = UploadedFile::fake()->create('doc.pdf', 100);

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/downloads', [
            'title' => 'Test',
            'file' => $file,
        ], ['Accept' => 'application/json']);

    $response->assertForbidden();
});

it('validates download store request', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->post('/api/downloads', [], ['Accept' => 'application/json']);

    $response->assertUnprocessable()->assertJsonValidationErrors(['title', 'file']);
});

it('updates download as super admin', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $download = Download::factory()->create(['title' => 'Old Title']);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/downloads/{$download->id}", [
            'title' => 'New Title',
            'description' => 'Updated description',
            'sort_order' => 2,
        ]);

    $response->assertSuccessful()->assertJsonPath('data.title', 'New Title');
    $download->refresh();
    expect($download->title)->toBe('New Title');
});

it('returns 401 when unauthenticated user tries to update download', function () {
    $download = Download::factory()->create();

    $response = $this->putJson("/api/downloads/{$download->id}", ['title' => 'Updated'], ['Accept' => 'application/json']);

    $response->assertUnauthorized();
});

it('deletes download as super admin', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $download = Download::factory()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/downloads/{$download->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('downloads', ['id' => $download->id]);
});

it('returns 401 when unauthenticated user tries to delete download', function () {
    $download = Download::factory()->create();

    $response = $this->deleteJson("/api/downloads/{$download->id}");

    $response->assertUnauthorized();
});
