<?php

use App\Enums\MembershipApplicationStatus;
use App\Mail\MembershipApprovedMail;
use App\Models\MembershipApplication;
use App\Models\User;
use App\PrimaryMemberType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('can submit a membership application', function () {
    $data = [
        'membership_type' => 'GENERAL',
        'full_name' => 'John Doe',
        'name_bangla' => 'জন ডো',
        'father_name' => 'Father Name',
        'gender' => 'MALE',
        'jsc_year' => 2010,
        'ssc_year' => 2012,
        'present_address' => '123 Main St',
        'permanent_address' => '456 Oak Ave',
        'mobile_number' => '1234567890',
        'profession' => 'Engineer',
        't_shirt_size' => 'L',
        'blood_group' => 'O+',
        'payment_years' => 1,
    ];

    $response = $this->postJson('/api/membership-applications', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'membership_type',
                'full_name',
                'status',
            ],
        ]);

    $this->assertDatabaseHas('membership_applications', [
        'full_name' => 'John Doe',
        'status' => MembershipApplicationStatus::Pending->value,
        'yearly_fee' => 500.0,
        'total_paid_amount' => 500.0,
    ]);
});

it('validates required fields when submitting application', function () {
    $response = $this->postJson('/api/membership-applications', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'membership_type',
            'full_name',
            'name_bangla',
            'father_name',
            'gender',
            'present_address',
            'permanent_address',
            'mobile_number',
            'profession',
            't_shirt_size',
            'blood_group',
            'payment_years',
        ]);
});

it('calculates yearly fee and total paid amount based on membership type and payment years', function () {
    $data = [
        'membership_type' => 'GENERAL',
        'full_name' => 'John Doe',
        'name_bangla' => 'জন ডো',
        'father_name' => 'Father Name',
        'gender' => 'MALE',
        'present_address' => '123 Main St',
        'permanent_address' => '456 Oak Ave',
        'mobile_number' => '1234567890',
        'profession' => 'Engineer',
        't_shirt_size' => 'L',
        'blood_group' => 'O+',
        'payment_years' => 2,
        'entry_fee' => 100,
    ];

    $response = $this->postJson('/api/membership-applications', $data);

    $response->assertSuccessful();

    $this->assertDatabaseHas('membership_applications', [
        'full_name' => 'John Doe',
        'yearly_fee' => 500.0,
        'total_paid_amount' => 1100.0, // (500 * 2) + 100
    ]);
});

it('calculates fees correctly for LIFETIME membership', function () {
    $data = [
        'membership_type' => 'LIFETIME',
        'full_name' => 'Jane Doe',
        'name_bangla' => 'জেন ডো',
        'father_name' => 'Father Name',
        'gender' => 'FEMALE',
        'present_address' => '123 Main St',
        'permanent_address' => '456 Oak Ave',
        'mobile_number' => '1234567890',
        'profession' => 'Doctor',
        't_shirt_size' => 'M',
        'blood_group' => 'A+',
        'payment_years' => 1,
    ];

    $response = $this->postJson('/api/membership-applications', $data);

    $response->assertSuccessful();

    $this->assertDatabaseHas('membership_applications', [
        'full_name' => 'Jane Doe',
        'yearly_fee' => 10000.0,
        'total_paid_amount' => 10000.0,
    ]);
});

it('calculates fees correctly for ASSOCIATE membership with 3 payment years', function () {
    $data = [
        'membership_type' => 'ASSOCIATE',
        'full_name' => 'Bob Smith',
        'name_bangla' => 'বব স্মিথ',
        'father_name' => 'Father Name',
        'gender' => 'MALE',
        'present_address' => '123 Main St',
        'permanent_address' => '456 Oak Ave',
        'mobile_number' => '1234567890',
        'profession' => 'Teacher',
        't_shirt_size' => 'XL',
        'blood_group' => 'B+',
        'payment_years' => 3,
        'entry_fee' => 50,
    ];

    $response = $this->postJson('/api/membership-applications', $data);

    $response->assertSuccessful();

    $this->assertDatabaseHas('membership_applications', [
        'full_name' => 'Bob Smith',
        'yearly_fee' => 300.0,
        'total_paid_amount' => 950.0, // (300 * 3) + 50
    ]);
});

it('can upload studentship proof file', function () {
    Storage::fake('public');

    $file = \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 1000);

    $data = [
        'membership_type' => 'GENERAL',
        'full_name' => 'John Doe',
        'name_bangla' => 'জন ডো',
        'father_name' => 'Father Name',
        'gender' => 'MALE',
        'present_address' => '123 Main St',
        'permanent_address' => '456 Oak Ave',
        'mobile_number' => '1234567890',
        'profession' => 'Engineer',
        't_shirt_size' => 'L',
        'blood_group' => 'O+',
        'payment_years' => 1,
        'studentship_proof_file' => $file,
    ];

    $response = $this->postJson('/api/membership-applications', $data);

    $response->assertSuccessful();

    $application = MembershipApplication::first();
    expect($application->studentship_proof_file)->not->toBeNull();
    Storage::disk('public')->assertExists($application->studentship_proof_file);
});

it('requires super admin to list applications', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/membership-applications');

    $response->assertForbidden();
});

it('allows super admin to list applications', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    MembershipApplication::factory()->count(3)->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/membership-applications');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('allows super admin to view single application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $application = MembershipApplication::factory()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson("/api/membership-applications/{$application->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $application->id);
});

it('allows super admin to approve application and create user', function () {
    Mail::fake();

    $superAdmin = User::factory()->superAdmin()->create();
    $application = MembershipApplication::factory()->create([
        'email' => 'test@example.com',
        'membership_type' => PrimaryMemberType::General,
        'ssc_year' => 2020,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application->id}/approve");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'message',
            'application',
            'user' => ['id', 'name', 'email', 'member_id'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'member_id' => 'G-2020-0001',
    ]);

    $application->refresh();
    expect($application->status)->toBe(MembershipApplicationStatus::Approved);
    expect($application->approved_by)->toBe($superAdmin->id);

    Mail::assertSent(MembershipApprovedMail::class, function ($mail) {
        return $mail->user->email === 'test@example.com';
    });
});

it('generates unique member IDs correctly', function () {
    Mail::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    // Create first application
    $application1 = MembershipApplication::factory()->create([
        'email' => 'test1@example.com',
        'membership_type' => PrimaryMemberType::General,
        'ssc_year' => 2020,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application1->id}/approve")
        ->assertSuccessful();

    // Create second application with same type and year
    $application2 = MembershipApplication::factory()->create([
        'email' => 'test2@example.com',
        'membership_type' => PrimaryMemberType::General,
        'ssc_year' => 2020,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application2->id}/approve")
        ->assertSuccessful();

    $user1 = User::where('email', 'test1@example.com')->first();
    $user2 = User::where('email', 'test2@example.com')->first();

    expect($user1->member_id)->toBe('G-2020-0001');
    expect($user2->member_id)->toBe('G-2020-0002');
});

it('uses SSC year for member ID when available, otherwise JSC year', function () {
    Mail::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    // Application with both SSC and JSC
    $application1 = MembershipApplication::factory()->create([
        'email' => 'test1@example.com',
        'membership_type' => PrimaryMemberType::General,
        'ssc_year' => 2020,
        'jsc_year' => 2018,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application1->id}/approve")
        ->assertSuccessful();

    $user1 = User::where('email', 'test1@example.com')->first();
    expect($user1->member_id)->toStartWith('G-2020-'); // Uses SSC year

    // Application with only JSC
    $application2 = MembershipApplication::factory()->create([
        'email' => 'test2@example.com',
        'membership_type' => PrimaryMemberType::Lifetime,
        'ssc_year' => null,
        'jsc_year' => 2018,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application2->id}/approve")
        ->assertSuccessful();

    $user2 = User::where('email', 'test2@example.com')->first();
    expect($user2->member_id)->toStartWith('LT-2018-'); // Uses JSC year
});

it('uses shared sequence for member IDs across all types and years', function () {
    Mail::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    // Create first member - General type, year 2000
    $application1 = MembershipApplication::factory()->create([
        'email' => 'test1@example.com',
        'membership_type' => PrimaryMemberType::General,
        'ssc_year' => 2000,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application1->id}/approve")
        ->assertSuccessful();

    $user1 = User::where('email', 'test1@example.com')->first();
    expect($user1->member_id)->toBe('G-2000-0001');

    // Create second member - Lifetime type, different year 2020
    $application2 = MembershipApplication::factory()->create([
        'email' => 'test2@example.com',
        'membership_type' => PrimaryMemberType::Lifetime,
        'ssc_year' => 2020,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application2->id}/approve")
        ->assertSuccessful();

    $user2 = User::where('email', 'test2@example.com')->first();
    expect($user2->member_id)->toBe('LT-2020-0002'); // Should be 0002, not 0001

    // Create third member - Associate type, yet another year
    $application3 = MembershipApplication::factory()->create([
        'email' => 'test3@example.com',
        'membership_type' => PrimaryMemberType::Associate,
        'ssc_year' => 2015,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application3->id}/approve")
        ->assertSuccessful();

    $user3 = User::where('email', 'test3@example.com')->first();
    expect($user3->member_id)->toBe('A-2015-0003'); // Should be 0003, continuing the sequence
});

it('generates correct member ID prefixes for different membership types', function () {
    Mail::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    $types = [
        [PrimaryMemberType::General, 'G'],
        [PrimaryMemberType::Lifetime, 'LT'],
        [PrimaryMemberType::Associate, 'A'],
    ];

    foreach ($types as $index => [$type, $prefix]) {
        $application = MembershipApplication::factory()->create([
            'email' => "test{$prefix}@example.com",
            'membership_type' => $type,
            'ssc_year' => 2020,
        ]);

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/membership-applications/{$application->id}/approve")
            ->assertSuccessful();

        $user = User::where('email', "test{$prefix}@example.com")->first();
        expect($user->member_id)->toStartWith("{$prefix}-2020-");

        // Verify sequential numbering (0001, 0002, 0003)
        $expectedNumber = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        expect($user->member_id)->toEndWith($expectedNumber);
    }
});

it('cannot approve application without email', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $application = MembershipApplication::factory()->create([
        'email' => null,
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application->id}/approve");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Application must have an email address to be approved.');
});

it('cannot approve already approved application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $application = MembershipApplication::factory()->approved()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application->id}/approve");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Application is not pending approval.');
});

it('allows super admin to reject application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $application = MembershipApplication::factory()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/membership-applications/{$application->id}/reject");

    $response->assertSuccessful();

    $application->refresh();
    expect($application->status)->toBe(MembershipApplicationStatus::Rejected);
    expect($application->approved_by)->toBe($superAdmin->id);
});

it('allows super admin to update application', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $application = MembershipApplication::factory()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/membership-applications/{$application->id}", [
            'full_name' => 'Updated Name',
        ]);

    $response->assertSuccessful();

    $application->refresh();
    expect($application->full_name)->toBe('Updated Name');
});

it('can filter applications by status', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    MembershipApplication::factory()->pending()->count(2)->create();
    MembershipApplication::factory()->approved()->count(3)->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/membership-applications?status=PENDING');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});
