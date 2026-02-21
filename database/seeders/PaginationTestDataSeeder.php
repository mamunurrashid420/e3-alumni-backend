<?php

namespace Database\Seeders;

use App\Enums\BloodGroup;
use App\Enums\PaymentStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Enums\SelfDeclarationStatus;
use App\Models\MemberProfile;
use App\Models\MembershipApplication;
use App\Models\MemberType;
use App\Models\Payment;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\SelfDeclaration;
use App\Models\User;
use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PaginationTestDataSeeder extends Seeder
{
    /**
     * Seed 50 dummy records for each paginated list to test pagination.
     */
    public function run(): void
    {
        $count = 50;

        // 1. Membership Applications (50) – mixed statuses
        $statuses = ['pending', 'approved', 'rejected'];
        for ($i = 0; $i < $count; $i++) {
            $state = $statuses[$i % 3];
            MembershipApplication::factory()->{$state}()->create();
        }

        // 2. Members / Users (50) with member_id and phone; each gets a MemberProfile with blood_group for Blood Donors list
        // Use high member number range (10001–10050) to avoid colliding with existing seed data
        $memberTypeIds = MemberType::pluck('id')->toArray();
        $users = [];
        $baseMemberNum = 10000;
        for ($i = 1; $i <= $count; $i++) {
            $memberId = 'G-2000-'.(string) ($baseMemberNum + $i);
            $user = User::create([
                'name' => fake()->name(),
                'email' => 'pagination.member.'.$i.'+'.fake()->unique()->safeEmail(),
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => UserRole::Member,
                'primary_member_type' => fake()->randomElement(PrimaryMemberType::cases()),
                'member_id' => $memberId,
                'phone' => '01'.str_pad((string) ($i + 90000000), 8, '0', STR_PAD_LEFT),
                'membership_expires_at' => now()->addYears(2),
            ]);
            $users[] = $user;
            MemberProfile::create([
                'user_id' => $user->id,
                'name_bangla' => fake()->name(),
                'blood_group' => fake()->randomElement(BloodGroup::cases())->value,
            ]);
        }

        // 3. Payments (50) – link to our members' member_id
        $paymentStatuses = [PaymentStatus::Pending, PaymentStatus::Approved, PaymentStatus::Rejected];
        foreach ($users as $index => $user) {
            $status = $paymentStatuses[$index % 3];
            Payment::factory()->create([
                'member_id' => $user->member_id,
                'name' => $user->name,
                'mobile_number' => $user->phone,
                'status' => $status,
                'approved_at' => $status !== PaymentStatus::Pending ? now() : null,
            ]);
        }

        // 4. Self Declarations (50) – one per member user
        $secondaryTypeId = $memberTypeIds[array_rand($memberTypeIds)] ?? 1;
        $selfDeclStatuses = [SelfDeclarationStatus::Pending, SelfDeclarationStatus::Approved, SelfDeclarationStatus::Rejected];
        foreach ($users as $index => $user) {
            $status = $selfDeclStatuses[$index % 3];
            SelfDeclaration::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'signature_file' => null,
                'secondary_member_type_id' => $secondaryTypeId,
                'date' => fake()->dateTimeBetween('-1 year'),
                'status' => $status,
                'approved_at' => $status !== SelfDeclarationStatus::Pending ? now() : null,
            ]);
        }

        // 5. Scholarship Applications (50)
        $scholarshipIds = Scholarship::pluck('id')->toArray();
        if (empty($scholarshipIds)) {
            $this->command->warn('No scholarships found. Run ScholarshipSeeder first. Skipping scholarship applications.');
        } else {
            $scholarStatuses = [ScholarshipApplicationStatus::Pending, ScholarshipApplicationStatus::Approved, ScholarshipApplicationStatus::Rejected];
            for ($i = 0; $i < $count; $i++) {
                $status = $scholarStatuses[$i % 3];
                ScholarshipApplication::factory()->create([
                    'scholarship_id' => $scholarshipIds[array_rand($scholarshipIds)],
                    'status' => $status,
                    'approved_at' => $status !== ScholarshipApplicationStatus::Pending ? now() : null,
                ]);
            }
        }

        $this->command->info("Pagination test data created: {$count} applications, {$count} members, {$count} payments, {$count} self-declarations, {$count} scholarship applications.");
    }
}
