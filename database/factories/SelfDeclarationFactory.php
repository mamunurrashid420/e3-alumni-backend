<?php

namespace Database\Factories;

use App\Enums\SelfDeclarationStatus;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SelfDeclaration>
 */
class SelfDeclarationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $secondaryTypeId = MemberType::query()->inRandomOrder()->value('id');

        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'signature_file' => null,
            'secondary_member_type_id' => $secondaryTypeId ?? 1,
            'date' => fake()->dateTimeBetween('-1 year'),
            'status' => SelfDeclarationStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => SelfDeclarationStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SelfDeclarationStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SelfDeclarationStatus::Rejected,
            'approved_at' => now(),
        ]);
    }
}
