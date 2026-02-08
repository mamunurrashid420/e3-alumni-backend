<?php

use App\Enums\SelfDeclarationStatus;
use App\Models\MemberType;
use App\Models\SelfDeclaration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('allows user to resubmit self-declaration after rejection', function () {
    $memberType = MemberType::factory()->create();
    $user = User::factory()->member()->create([
        'secondary_member_type_id' => null,
    ]);

    SelfDeclaration::create([
        'user_id' => $user->id,
        'name' => 'Previous Name',
        'signature_file' => 'old/signature.png',
        'secondary_member_type_id' => $memberType->id,
        'date' => now()->subDays(5),
        'status' => SelfDeclarationStatus::Rejected,
        'rejected_reason' => 'Please correct the signature.',
    ]);

    $file = UploadedFile::fake()->create('signature.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/self-declarations', [
            'name' => 'New Name',
            'signature_file' => $file,
            'secondary_member_type_id' => $memberType->id,
            'date' => now()->format('Y-m-d'),
        ], [
            'Accept' => 'application/json',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', SelfDeclarationStatus::Pending->value)
        ->assertJsonPath('data.name', 'New Name');

    expect(SelfDeclaration::where('user_id', $user->id)->count())->toBe(2);
});

it('rejects submission when user has pending self-declaration', function () {
    $memberType = MemberType::factory()->create();
    $user = User::factory()->member()->create([
        'secondary_member_type_id' => null,
    ]);

    SelfDeclaration::create([
        'user_id' => $user->id,
        'name' => 'Pending Name',
        'signature_file' => 'pending/signature.png',
        'secondary_member_type_id' => $memberType->id,
        'date' => now(),
        'status' => SelfDeclarationStatus::Pending,
    ]);

    $file = UploadedFile::fake()->create('signature.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/self-declarations', [
            'name' => 'Another Name',
            'signature_file' => $file,
            'secondary_member_type_id' => $memberType->id,
            'date' => now()->format('Y-m-d'),
        ], [
            'Accept' => 'application/json',
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'You already have a pending self-declaration.');

    expect(SelfDeclaration::where('user_id', $user->id)->count())->toBe(1);
});

it('includes latest_self_declaration in current user response', function () {
    $memberType = MemberType::factory()->create();
    $user = User::factory()->member()->create([
        'secondary_member_type_id' => null,
    ]);

    SelfDeclaration::create([
        'user_id' => $user->id,
        'name' => 'Test Name',
        'signature_file' => 'test/signature.png',
        'secondary_member_type_id' => $memberType->id,
        'date' => now(),
        'status' => SelfDeclarationStatus::Rejected,
        'rejected_reason' => 'Incorrect format.',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user');

    $response->assertSuccessful()
        ->assertJsonPath('latest_self_declaration.status', SelfDeclarationStatus::Rejected->value)
        ->assertJsonPath('latest_self_declaration.rejected_reason', 'Incorrect format.');
});
