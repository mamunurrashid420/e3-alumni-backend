<?php

namespace Database\Factories;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipApplication>
 */
class ScholarshipApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scholarshipId = Scholarship::query()->inRandomOrder()->value('id') ?? 1;

        return [
            'scholarship_id' => $scholarshipId,
            'applicant_name' => fake()->name(),
            'applicant_email' => fake()->unique()->safeEmail(),
            'applicant_phone' => fake()->numerify('01########'),
            'applicant_address' => fake()->address(),
            'class_or_grade' => fake()->randomElement(['6', '7', '8', '9', '10', 'SSC']),
            'school_name' => fake()->company().' School',
            'parent_or_guardian_name' => fake()->name('male'),
            'academic_proof_file' => null,
            'other_document_file' => null,
            'statement' => fake()->paragraph(),
            'applicant_signature' => null,
            'user_id' => null,
            'status' => ScholarshipApplicationStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ScholarshipApplicationStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScholarshipApplicationStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScholarshipApplicationStatus::Rejected,
            'approved_at' => now(),
        ]);
    }
}
