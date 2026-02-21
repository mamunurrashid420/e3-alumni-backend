<?php

use App\Enums\HonorBoardRole;
use App\Models\AdvisoryBodyMember;
use App\Models\BatchRepresentative;
use App\Models\ConveningCommitteeMember;
use App\Models\HonorBoardEntry;
use App\Models\MemberType;
use App\Models\User;
use App\PrimaryMemberType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- Public Members (paginated) ---

it('returns paginated members list publicly', function () {
    User::factory()->member()->create(['member_id' => 'G-2000-0001', 'name' => 'Alice Member']);
    User::factory()->member()->create(['member_id' => 'LT-2001-0002', 'name' => 'Bob Member']);
    User::factory()->superAdmin()->create(); // should not appear

    $response = $this->getJson('/api/about/members');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'member_id', 'primary_member_type', 'designation', 'profession']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next'],
        ])
        ->assertJsonCount(2, 'data');
    $names = array_column($response->json('data'), 'name');
    expect($names)->toContain('Alice Member')->toContain('Bob Member');
});

it('returns members filtered by primary_member_type when query param provided', function () {
    User::factory()->member()->create(['member_id' => 'G-2000-0001', 'primary_member_type' => PrimaryMemberType::General]);
    User::factory()->member()->create(['member_id' => 'LT-2001-0002', 'primary_member_type' => PrimaryMemberType::Lifetime]);

    $response = $this->getJson('/api/about/members?primary_member_type='.PrimaryMemberType::General->value);

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.primary_member_type', PrimaryMemberType::General->value);
});

it('excludes users without member_id from public members list', function () {
    User::factory()->member()->create(['member_id' => null, 'name' => 'No Id']);
    User::factory()->member()->create(['member_id' => 'G-2000-0001', 'name' => 'With Id']);

    $response = $this->getJson('/api/about/members');

    $response->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'With Id');
});

it('returns only members with secondary type when has_secondary_type is true', function () {
    $memberType = MemberType::factory()->create(['name' => 'Executive']);
    User::factory()->member()->create(['member_id' => 'G-2000-0001', 'name' => 'Without Secondary']);
    User::factory()->member()->withSecondaryMemberType($memberType->id)->create(['member_id' => 'G-2000-0002', 'name' => 'With Secondary']);

    $response = $this->getJson('/api/about/members?has_secondary_type=1');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'With Secondary')
        ->assertJsonPath('data.0.secondary_member_type.name', 'Executive');
});

// --- Convening Committee ---

it('returns convening committee list publicly', function () {
    ConveningCommitteeMember::factory()->count(2)->create();

    $response = $this->getJson('/api/about/convening-committee');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name', 'mobile_number', 'designation', 'occupation', 'sort_order']]])
        ->assertJsonCount(2, 'data');
});

it('stores convening committee member as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $data = [
        'name' => 'John Doe',
        'mobile_number' => '01712345678',
        'designation' => 'Chairman',
        'occupation' => 'Engineer',
        'sort_order' => 1,
    ];

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/convening-committee', $data);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'John Doe');
    $this->assertDatabaseHas('convening_committee_members', ['name' => 'John Doe']);
});

it('forbids storing convening committee member as non super admin', function () {
    $user = User::factory()->member()->create();
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/about/convening-committee', ['name' => 'Test', 'mobile_number' => '017']);
    $response->assertForbidden();
});

it('validates convening committee store request', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/convening-committee', []);
    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

// --- Advisory Body ---

it('returns advisory body list publicly', function () {
    AdvisoryBodyMember::factory()->count(2)->create();

    $response = $this->getJson('/api/about/advisory-body');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name', 'mobile_number', 'designation', 'occupation', 'sort_order']]])
        ->assertJsonCount(2, 'data');
});

it('stores advisory body member as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $data = ['name' => 'Jane Doe', 'designation' => 'Advisor', 'sort_order' => 1];

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/advisory-body', $data);

    $response->assertCreated()->assertJsonPath('data.name', 'Jane Doe');
    $this->assertDatabaseHas('advisory_body_members', ['name' => 'Jane Doe']);
});

it('forbids storing advisory body member as non super admin', function () {
    $user = User::factory()->member()->create();
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/about/advisory-body', ['name' => 'Test']);
    $response->assertForbidden();
});

// --- Honor Board ---

it('returns honor board list publicly', function () {
    HonorBoardEntry::factory()->president()->create();
    HonorBoardEntry::factory()->generalSecretary()->create();

    $response = $this->getJson('/api/about/honor-board');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'role', 'name', 'member_id', 'durations', 'sort_order']]])
        ->assertJsonCount(2, 'data');
});

it('returns honor board filtered by role when role query param provided', function () {
    HonorBoardEntry::factory()->president()->count(2)->create();
    HonorBoardEntry::factory()->generalSecretary()->create();

    $response = $this->getJson('/api/about/honor-board?role='.HonorBoardRole::President->value);

    $response->assertSuccessful()->assertJsonCount(2, 'data');
});

it('stores honor board entry as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $data = [
        'role' => HonorBoardRole::President->value,
        'name' => 'President Name',
        'member_id' => '1234',
        'durations' => '2020-2024',
        'sort_order' => 1,
    ];

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/honor-board', $data);

    $response->assertCreated()->assertJsonPath('data.role', HonorBoardRole::President->value);
    $this->assertDatabaseHas('honor_board_entries', ['name' => 'President Name']);
});

it('forbids storing honor board entry as non super admin', function () {
    $user = User::factory()->member()->create();
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/about/honor-board', ['role' => HonorBoardRole::President->value, 'name' => 'Test']);
    $response->assertForbidden();
});

it('validates honor board store request', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/honor-board', []);
    $response->assertUnprocessable()->assertJsonValidationErrors(['role', 'name']);
});

// --- Batch Representatives ---

it('returns batch representatives list publicly', function () {
    BatchRepresentative::factory()->count(2)->create();

    $response = $this->getJson('/api/about/batch-representatives');

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name', 'mobile_number', 'ssc_batch', 'sort_order']]])
        ->assertJsonCount(2, 'data');
});

it('stores batch representative as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $data = ['name' => 'Batch Rep', 'mobile_number' => '01812345678', 'ssc_batch' => '2005', 'sort_order' => 1];

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/batch-representatives', $data);

    $response->assertCreated()->assertJsonPath('data.name', 'Batch Rep');
    $this->assertDatabaseHas('batch_representatives', ['name' => 'Batch Rep']);
});

it('forbids storing batch representative as non super admin', function () {
    $user = User::factory()->member()->create();
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/about/batch-representatives', ['name' => 'Test']);
    $response->assertForbidden();
});

it('validates batch representative store request', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $response = $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/about/batch-representatives', []);
    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

// --- Update and destroy (super admin only) ---

it('updates convening committee member as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = ConveningCommitteeMember::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->putJson("/api/about/convening-committee/{$member->id}", ['name' => 'New Name', 'sort_order' => 2]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'New Name');
    $member->refresh();
    expect($member->name)->toBe('New Name');
});

it('deletes convening committee member as super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $member = ConveningCommitteeMember::factory()->create();

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/about/convening-committee/{$member->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('convening_committee_members', ['id' => $member->id]);
});
