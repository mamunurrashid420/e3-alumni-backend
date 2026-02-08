<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows super admin to disable a member', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/members/{$member->id}/disable");

    $response->assertSuccessful()
        ->assertJsonPath('data.disabled_at', fn ($v) => $v !== null);

    $member->refresh();
    expect($member->disabled_at)->not->toBeNull();
});

it('allows super admin to re-enable a disabled member', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create(['disabled_at' => now()]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/members/{$member->id}/enable");

    $response->assertSuccessful()
        ->assertJsonPath('data.disabled_at', null);

    $member->refresh();
    expect($member->disabled_at)->toBeNull();
});

it('allows super admin to delete a member', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/members/{$member->id}");

    $response->assertSuccessful()
        ->assertJson(['message' => 'Member deleted successfully.']);

    $this->assertModelMissing($member);
});

it('prevents super admin from deleting their own account', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/members/{$superAdmin->id}");

    $response->assertStatus(422)
        ->assertJson(['message' => 'You cannot delete your own account.']);

    $this->assertModelExists($superAdmin);
});

it('forbids member from disabling another member', function () {
    $memberAsActor = User::factory()->member()->create();
    $memberToDisable = User::factory()->member()->create();

    $response = $this->actingAs($memberAsActor, 'sanctum')
        ->postJson("/api/members/{$memberToDisable->id}/disable");

    $response->assertForbidden();
});

it('forbids member from deleting another member', function () {
    $memberAsActor = User::factory()->member()->create();
    $memberToDelete = User::factory()->member()->create();

    $response = $this->actingAs($memberAsActor, 'sanctum')
        ->deleteJson("/api/members/{$memberToDelete->id}");

    $response->assertForbidden();
});

it('returns 404 when disabling a super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $otherSuperAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/members/{$otherSuperAdmin->id}/disable");

    $response->assertNotFound();
});

it('rejects login for disabled member', function () {
    $member = User::factory()->member()->create(['disabled_at' => now()]);

    $response = $this->postJson('/api/login', [
        'email_or_phone' => $member->email,
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email_or_phone'])
        ->assertJsonPath('errors.email_or_phone.0', 'This account has been disabled. Please contact support.');
});
