<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    protected $model = \App\Models\Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'company_name' => fake()->optional()->company(),
            'logo' => null,
            'status' => JobStatus::Active,
            'application_url' => fake()->optional()->url(),
            'closes_at' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::Expired]);
    }
}
