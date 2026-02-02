<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConveningCommitteeMember>
 */
class ConveningCommitteeMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile_number' => fake()->optional()->numerify('01########'),
            'designation' => fake()->optional()->jobTitle(),
            'occupation' => fake()->optional()->jobTitle(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
