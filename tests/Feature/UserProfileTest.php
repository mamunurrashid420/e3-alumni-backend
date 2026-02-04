<?php

use App\Enums\MembershipApplicationStatus;
use App\Models\MemberProfile;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Notifications\MembershipApprovedSms;
use App\PrimaryMemberType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

it('allows authenticated member to update profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->member()->create(['phone' => '01700000001']);
    MemberProfile::create([
        'user_id' => $user->id,
        'present_address' => 'Address',
    ]);

    $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/user/profile', [
            'photo' => $file,
            '_method' => 'PUT',
        ], [
            'Accept' => 'application/json',
        ]);

    $response->assertSuccessful();
    $user->memberProfile->refresh();
    expect($user->memberProfile->photo)->not->toBeNull();
    Storage::disk('public')->assertExists($user->memberProfile->photo);
    expect($response->json('profile.photo'))->not->toBeNull();
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

it('returns membership_expires_at for member with approved general application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Expiry Test Member',
        'email' => 'expiry-member@test.com',
        'phone' => '01711111111',
        'member_id' => 'G-2000-0099',
        'primary_member_type' => PrimaryMemberType::General,
    ]);

    MembershipApplication::factory()->create([
        'email' => 'expiry-member@test.com',
        'membership_type' => PrimaryMemberType::General,
        'payment_years' => '3',
        'status' => MembershipApplicationStatus::Approved,
        'approved_at' => now()->setDate(2025, 6, 15),
        'approved_by' => $superAdmin->id,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/members/{$member->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.membership_expires_at', fn ($v) => str_contains((string) $v, '2027-12-31'));
});

it('returns null membership_expires_at for lifetime member', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Lifetime Member',
        'email' => 'lifetime@test.com',
        'phone' => '01722222222',
        'member_id' => 'LT-2000-0098',
        'primary_member_type' => PrimaryMemberType::Lifetime,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/members/{$member->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.membership_expires_at', null);
});

it('allows super admin to renew membership and extends expiry', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Renew Test Member',
        'email' => 'renew@test.com',
        'phone' => '01733333333',
        'member_id' => 'G-2000-0097',
        'primary_member_type' => PrimaryMemberType::General,
        'membership_expires_at' => now()->addYears(1)->endOfYear()->endOfDay(),
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/members/{$member->id}/renew-membership", ['years' => 2]);

    $expectedYear = now()->addYears(3)->year;
    $response->assertSuccessful()
        ->assertJsonPath('data.membership_expires_at', fn ($v) => $v !== null && str_contains((string) $v, (string) $expectedYear));

    $member->refresh();
    expect($member->membership_expires_at->format('Y'))->toBe((string) $expectedYear);
});

it('rejects renew membership for lifetime member', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = User::factory()->member()->create([
        'name' => 'Lifetime Member',
        'email' => 'lifetime-renew@test.com',
        'phone' => '01744444444',
        'member_id' => 'LT-2000-0096',
        'primary_member_type' => PrimaryMemberType::Lifetime,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/members/{$member->id}/renew-membership", ['years' => 1]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Lifetime membership does not expire and cannot be renewed.');
});
