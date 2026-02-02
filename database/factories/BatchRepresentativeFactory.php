<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BatchRepresentative>
 */
class BatchRepresentativeFactory extends Factory
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
            'ssc_batch' => fake()->optional()->regexify('[0-9]{4}'),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
