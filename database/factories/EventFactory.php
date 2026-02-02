<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+1 year');
        $endAt = (clone $startAt)->modify('+'.fake()->numberBetween(1, 8).' hours');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'location' => fake()->city().', '.fake()->country(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => EventStatus::Open,
            'cover_photo' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EventStatus::Draft]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EventStatus::Open]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EventStatus::Closed]);
    }
}
