<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Console\Command;

class MigrateMemberProfilesFromApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:backfill-profiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MemberProfile for existing members from their approved membership application (run once after deploying member_profiles).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $members = User::query()
            ->where('role', UserRole::Member)
            ->with('memberProfile')
            ->get();

        $created = 0;
        $skipped = 0;
        $noApplication = 0;

        foreach ($members as $user) {
            if ($user->memberProfile) {
                $skipped++;

                continue;
            }

            $application = $user->membershipApplication();
            if (! $application) {
                $noApplication++;
                $this->warn("No approved application found for member user id {$user->id} ({$user->name}).");

                continue;
            }

            $files = MemberProfile::copyFilesFromApplicationPath(
                $application->photo,
                $application->signature,
                $user->id
            );

            MemberProfile::create([
                'user_id' => $user->id,
                'name_bangla' => $application->name_bangla,
                'father_name' => $application->father_name,
                'mother_name' => $application->mother_name,
                'gender' => $application->gender?->value,
                'jsc_year' => $application->jsc_year,
                'ssc_year' => $application->ssc_year,
                'highest_educational_degree' => $application->highest_educational_degree,
                'present_address' => $application->present_address,
                'permanent_address' => $application->permanent_address,
                'profession' => $application->profession,
                'designation' => $application->designation,
                'institute_name' => $application->institute_name,
                't_shirt_size' => $application->t_shirt_size?->value,
                'blood_group' => $application->blood_group?->value,
                'photo' => $files['photo'],
                'signature' => $files['signature'],
            ]);
            $created++;
        }

        $this->info("Created {$created} member profile(s). Skipped {$skipped} (already had profile). {$noApplication} member(s) had no approved application.");

        $this->info('You can backfill membership expiry for existing members with: php artisan members:backfill-expiry');

        return self::SUCCESS;
    }
}
