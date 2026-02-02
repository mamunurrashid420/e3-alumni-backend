<?php

namespace Database\Factories;

use App\Enums\HonorBoardRole;
use App\Models\HonorBoardEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HonorBoardEntry>
 */
class HonorBoardEntryFactory extends Factory
{
    protected $model = HonorBoardEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => fake()->randomElement(HonorBoardRole::cases()),
            'name' => fake()->name(),
            'member_id' => fake()->optional()->numerify('####'),
            'durations' => fake()->optional()->regexify('[0-9]{4}-[0-9]{4}'),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function president(): static
    {
        return $this->state(fn (array $attributes) => ['role' => HonorBoardRole::President]);
    }

    public function generalSecretary(): static
    {
        return $this->state(fn (array $attributes) => ['role' => HonorBoardRole::GeneralSecretary]);
    }
}
