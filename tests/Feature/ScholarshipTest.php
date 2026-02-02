<?php

use App\Enums\ScholarshipApplicationStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('returns active scholarships for public', function () {
    Scholarship::create([
        'title' => 'Active Scholarship',
        'description' => 'Desc',
        'category' => 'Students',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Scholarship::create([
        'title' => 'Inactive Scholarship',
        'description' => 'Desc',
        'category' => 'Other',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    $response = $this->getJson('/api/scholarships');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active Scholarship');
});

it('allows super admin to see all scholarships', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    Scholarship::create([
        'title' => 'Active',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Scholarship::create([
        'title' => 'Inactive',
        'description' => 'Desc',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/scholarships');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can submit a scholarship application as guest', function () {
    $scholarship = Scholarship::create([
        'title' => 'Test Scholarship',
        'description' => 'Desc',
        'category' => 'Students',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $data = [
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'John Doe',
        'applicant_email' => 'john@example.com',
        'applicant_phone' => '01712345678',
        'applicant_address' => '123 Main St',
    ];

    $response = $this->postJson('/api/scholarship-applications', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'scholarship_id',
                'applicant_name',
                'status',
            ],
        ])
        ->assertJsonPath('data.applicant_name', 'John Doe')
        ->assertJsonPath('data.user_id', null);

    $this->assertDatabaseHas('scholarship_applications', [
        'applicant_name' => 'John Doe',
        'status' => ScholarshipApplicationStatus::Pending->value,
        'user_id' => null,
    ]);
});

it('sets user_id when submitting scholarship application as logged-in member', function () {
    $user = User::factory()->member()->create();
    $scholarship = Scholarship::create([
        'title' => 'Test Scholarship',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $data = [
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'Jane Doe',
        'applicant_phone' => '01812345678',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/scholarship-applications', $data);

    $response->assertSuccessful();

    $this->assertDatabaseHas('scholarship_applications', [
        'applicant_name' => 'Jane Doe',
        'user_id' => $user->id,
    ]);
});

it('validates required fields when submitting scholarship application', function () {
    $response = $this->postJson('/api/scholarship-applications', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'scholarship_id',
            'applicant_name',
            'applicant_phone',
        ]);
});

it('forbids unauthenticated user from creating scholarship', function () {
    $response = $this->postJson('/api/scholarships', [
        'title' => 'New Scholarship',
        'description' => 'Desc',
        'is_active' => true,
    ]);

    $response->assertUnauthorized();
});

it('forbids member from creating scholarship', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/scholarships', [
            'title' => 'New Scholarship',
            'description' => 'Desc',
            'is_active' => true,
        ]);

    $response->assertForbidden();
});

it('allows super admin to create scholarship', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/scholarships', [
            'title' => 'New Scholarship',
            'description' => 'Description',
            'category' => 'Students',
            'is_active' => true,
            'sort_order' => 1,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.title', 'New Scholarship');

    $this->assertDatabaseHas('scholarships', ['title' => 'New Scholarship']);
});

it('allows super admin to update and delete scholarship', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::create([
        'title' => 'Original',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/scholarships/{$scholarship->id}", [
            'title' => 'Updated Title',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('scholarships', ['title' => 'Updated Title']);

    $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/scholarships/{$scholarship->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('scholarships', ['id' => $scholarship->id]);
});

it('requires super admin to list scholarship applications', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/scholarship-applications');

    $response->assertForbidden();
});

it('allows super admin to list and show scholarship applications', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::create([
        'title' => 'Test',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $application = ScholarshipApplication::create([
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'Applicant',
        'applicant_phone' => '01712345678',
        'status' => ScholarshipApplicationStatus::Pending,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/scholarship-applications')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/scholarship-applications/{$application->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $application->id);
});

it('allows super admin to approve pending scholarship application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::create([
        'title' => 'Test',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $application = ScholarshipApplication::create([
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'Applicant',
        'applicant_phone' => '01712345678',
        'status' => ScholarshipApplicationStatus::Pending,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/scholarship-applications/{$application->id}/approve");

    $response->assertSuccessful()
        ->assertJsonPath('data.status', ScholarshipApplicationStatus::Approved->value);

    $application->refresh();
    expect($application->status)->toBe(ScholarshipApplicationStatus::Approved);
    expect($application->approved_by)->toBe($superAdmin->id);
});

it('rejects approve when scholarship application is not pending', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::create([
        'title' => 'Test',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $application = ScholarshipApplication::create([
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'Applicant',
        'applicant_phone' => '01712345678',
        'status' => ScholarshipApplicationStatus::Approved,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/scholarship-applications/{$application->id}/approve");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Application is not pending approval.');
});

it('allows super admin to reject pending scholarship application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::create([
        'title' => 'Test',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $application = ScholarshipApplication::create([
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'Applicant',
        'applicant_phone' => '01712345678',
        'status' => ScholarshipApplicationStatus::Pending,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/scholarship-applications/{$application->id}/reject", [
            'rejected_reason' => 'Incomplete documents',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.status', ScholarshipApplicationStatus::Rejected->value);

    $application->refresh();
    expect($application->status)->toBe(ScholarshipApplicationStatus::Rejected);
    expect($application->rejected_reason)->toBe('Incomplete documents');
});

it('rejects reject when scholarship application is not pending', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::create([
        'title' => 'Test',
        'description' => 'Desc',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $application = ScholarshipApplication::create([
        'scholarship_id' => $scholarship->id,
        'applicant_name' => 'Applicant',
        'applicant_phone' => '01712345678',
        'status' => ScholarshipApplicationStatus::Rejected,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/scholarship-applications/{$application->id}/reject");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Application is not pending approval.');
});
