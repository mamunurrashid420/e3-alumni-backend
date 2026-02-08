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
        $eventAt = fake()->dateTimeBetween('+1 month', '+1 year');
        $regOpensAt = now()->copy()->subDays(fake()->numberBetween(1, 14));
        $regClosesAt = (clone $eventAt)->modify('-1 day');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'short_description' => fake()->optional()->sentence(12),
            'location' => fake()->city().', '.fake()->country(),
            'event_at' => $eventAt,
            'registration_opens_at' => $regOpensAt,
            'registration_closes_at' => $regClosesAt,
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
