<?php

namespace Database\Factories;

use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Member,
            'primary_member_type' => fake()->randomElement(PrimaryMemberType::cases()),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SuperAdmin,
            'primary_member_type' => null,
            'secondary_member_type_id' => null,
        ]);
    }

    /**
     * Indicate that the user is a member.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Member,
            'primary_member_type' => fake()->randomElement(PrimaryMemberType::cases()),
        ]);
    }

    /**
     * Indicate that the user has a specific primary member type.
     */
    public function withPrimaryMemberType(PrimaryMemberType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Member,
            'primary_member_type' => $type,
        ]);
    }

    /**
     * Indicate that the user has a secondary member type.
     */
    public function withSecondaryMemberType(int $memberTypeId): static
    {
        return $this->state(fn (array $attributes) => [
            'secondary_member_type_id' => $memberTypeId,
        ]);
    }
}
