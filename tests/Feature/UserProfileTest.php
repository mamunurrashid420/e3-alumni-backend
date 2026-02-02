<?php

use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\MembershipApprovedSms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('allows authenticated member to update own profile', function () {
    $user = User::factory()->member()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '01700000001',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '01700000002',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Updated Name')
        ->assertJsonPath('email', 'updated@example.com')
        ->assertJsonPath('phone', '01700000002');

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->phone)->toBe('01700000002');
});

it('normalizes phone to 11 digits when updating profile', function () {
    $user = User::factory()->member()->create([
        'phone' => '01700000001',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '8801700000003',
        ]);

    $response->assertSuccessful();
    $user->refresh();
    expect($user->phone)->toBe('01700000003');
});

it('rejects profile update when phone is taken by another user', function () {
    User::factory()->member()->create(['phone' => '01700000099']);
    $user = User::factory()->member()->create([
        'name' => 'Me',
        'email' => 'me@example.com',
        'phone' => '01700000001',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user', [
            'name' => 'Me',
            'email' => 'me@example.com',
            'phone' => '01700000099',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['phone']);
});

it('allows keeping own phone when updating profile', function () {
    $user = User::factory()->member()->create([
        'name' => 'Me',
        'email' => 'me@example.com',
        'phone' => '01700000001',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user', [
            'name' => 'New Name',
            'email' => 'me@example.com',
            'phone' => '01700000001',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('phone', '01700000001');
});

it('allows authenticated member to update own member profile', function () {
    $user = User::factory()->member()->create(['phone' => '01700000001']);
    MemberProfile::create([
        'user_id' => $user->id,
        'present_address' => 'Old Address',
        'profession' => 'Old Profession',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user/profile', [
            'present_address' => 'New Address',
            'permanent_address' => 'Permanent Address',
            'profession' => 'Engineer',
            'designation' => 'Senior Dev',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('profile.present_address', 'New Address')
        ->assertJsonPath('profile.profession', 'Engineer')
        ->assertJsonPath('profile.designation', 'Senior Dev');

    $user->memberProfile->refresh();
    expect($user->memberProfile->present_address)->toBe('New Address');
    expect($user->memberProfile->profession)->toBe('Engineer');
});

it('returns 404 when updating member profile and user has no profile', function () {
    $user = User::factory()->member()->create(['phone' => '01700000001']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user/profile', [
            'present_address' => 'Some Address',
            'profession' => 'Engineer',
        ]);

    $response->assertNotFound();
});

it('rejects unauthenticated profile update', function () {
    $response = $this->putJson('/api/user', [
        'name' => 'Any',
        'email' => 'any@example.com',
        'phone' => '01700000001',
    ]);

    $response->assertUnauthorized();
});

it('rejects profile update without required phone', function () {
    $user = User::factory()->member()->create(['phone' => '01700000001']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/user', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['phone']);
});

it('allows super admin to update member and returns phone_changed when phone changed', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Member One',
        'email' => 'member@example.com',
        'phone' => '01700000001',
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/members/{$member->id}", [
            'name' => 'Member One',
            'email' => 'member@example.com',
            'phone' => '01700000002',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Member One')
        ->assertJsonPath('phone', '01700000002')
        ->assertJsonPath('phone_changed', true);

    $member->refresh();
    expect($member->phone)->toBe('01700000002');
});

it('returns phone_changed false when admin updates member without changing phone', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Member One',
        'email' => 'member@example.com',
        'phone' => '01700000001',
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/members/{$member->id}", [
            'name' => 'Updated Name Only',
            'email' => 'member@example.com',
            'phone' => '01700000001',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Updated Name Only')
        ->assertJsonPath('phone_changed', false);
});

it('forbids non super admin from updating member', function () {
    $memberUser = User::factory()->member()->create(['phone' => '01700000001']);
    $otherMember = User::factory()->member()->create([
        'name' => 'Other',
        'phone' => '01700000002',
    ]);

    $response = $this->actingAs($memberUser, 'sanctum')
        ->putJson("/api/members/{$otherMember->id}", [
            'name' => 'Hacked',
            'email' => $otherMember->email,
            'phone' => '01700000002',
        ]);

    $response->assertForbidden();
});

it('sends resend sms to updated phone after admin changes member phone', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Member',
        'phone' => '01700000001',
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/members/{$member->id}", [
            'name' => 'Member',
            'email' => $member->email,
            'phone' => '01700000002',
        ])
        ->assertSuccessful();

    $member->refresh();
    expect($member->phone)->toBe('01700000002');

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/members/{$member->id}/resend-sms")
        ->assertSuccessful();

    Notification::assertSentTo($member, MembershipApprovedSms::class);
});
