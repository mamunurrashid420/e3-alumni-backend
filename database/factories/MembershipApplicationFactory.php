<?php

namespace Database\Factories;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MembershipApplicationStatus;
use App\Enums\StudentshipProofType;
use App\Enums\TShirtSize;
use App\PrimaryMemberType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipApplication>
 */
class MembershipApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $membershipType = fake()->randomElement(PrimaryMemberType::cases());
        $yearlyFee = match ($membershipType) {
            PrimaryMemberType::General => 500,
            PrimaryMemberType::Lifetime => 10000,
            PrimaryMemberType::Associate => 300,
        };

        return [
            'membership_type' => $membershipType,
            'full_name' => fake()->name(),
            'name_bangla' => fake()->name(),
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->optional()->name('female'),
            'gender' => fake()->randomElement(Gender::cases()),
            'jsc_year' => fake()->optional()->numberBetween(2000, 2020),
            'ssc_year' => fake()->optional()->numberBetween(2000, 2020),
            'studentship_proof_type' => fake()->optional()->randomElement(StudentshipProofType::cases()),
            'studentship_proof_file' => null,
            'highest_educational_degree' => fake()->optional()->sentence(),
            'present_address' => fake()->address(),
            'permanent_address' => fake()->address(),
            'email' => fake()->optional()->safeEmail(),
            'mobile_number' => fake()->phoneNumber(),
            'profession' => fake()->jobTitle(),
            'designation' => fake()->optional()->jobTitle(),
            'institute_name' => fake()->optional()->company(),
            't_shirt_size' => fake()->randomElement(TShirtSize::cases()),
            'blood_group' => fake()->randomElement(BloodGroup::cases()),
            'entry_fee' => fake()->optional()->randomFloat(2, 0, 1000),
            'yearly_fee' => $yearlyFee,
            'payment_years' => fake()->randomElement([1, 2, 3]),
            'total_paid_amount' => fake()->randomFloat(2, $yearlyFee, $yearlyFee * 3),
            'receipt_file' => null,
            'status' => MembershipApplicationStatus::Pending,
        ];
    }

    /**
     * Indicate that the application is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipApplicationStatus::Pending,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the application is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipApplicationStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the application is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipApplicationStatus::Rejected,
            'approved_at' => now(),
        ]);
    }
}
