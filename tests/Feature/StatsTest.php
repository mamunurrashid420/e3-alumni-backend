<?php

use App\Models\Event;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns homepage stats publicly', function () {
    User::factory()->count(3)->create([
        'role' => UserRole::Member,
        'member_id' => fn () => 'M'.fake()->unique()->numberBetween(1000, 9999),
    ]);
    Event::factory()->count(2)->create();

    $response = $this->getJson('/api/stats');

    $response->assertSuccessful()
        ->assertJsonStructure(['members', 'events', 'photos', 'awards'])
        ->assertJsonPath('members', 3)
        ->assertJsonPath('events', 2);
    expect($response->json('photos'))->toBeInt();
    expect($response->json('awards'))->toBeInt();
});
