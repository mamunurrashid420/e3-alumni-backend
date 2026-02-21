<?php

namespace Database\Factories;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seq = fake()->unique()->numberBetween(1000, 99999);

        return [
            'member_id' => 'G-2000-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'address' => fake()->address(),
            'mobile_number' => fake()->unique()->numerify('01########'),
            'payment_purpose' => fake()->randomElement(PaymentPurpose::cases()),
            'payment_amount' => fake()->randomFloat(2, 300, 15000),
            'payment_method' => 'BANK_TRANSFER',
            'payment_proof_file' => null,
            'receipt_file' => null,
            'status' => PaymentStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PaymentStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Rejected,
            'approved_at' => now(),
        ]);
    }
}
