<?php

namespace App\Console\Commands;

use App\Models\User;
use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Console\Command;

class BackfillMembershipExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:backfill-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill membership_expires_at for existing members from their approved application (GENERAL/ASSOCIATE only). Safe to run multiple times.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $members = User::query()
            ->where('role', UserRole::Member)
            ->whereNull('membership_expires_at')
            ->whereIn('primary_member_type', [PrimaryMemberType::General, PrimaryMemberType::Associate])
            ->get();

        $updated = 0;
        $noApplication = 0;
        $noExpiry = 0;

        foreach ($members as $user) {
            $application = $user->membershipApplication();
            if (! $application || ! $application->approved_at) {
                $noApplication++;
                $this->warn("No approved application for member id {$user->id} ({$user->name}).");

                continue;
            }

            $years = (int) $application->payment_years;
            $expiresAt = User::computeMembershipExpiresAt($application->approved_at, $years);
            if (! $expiresAt) {
                $noExpiry++;
                $this->warn("Could not compute expiry for member id {$user->id} ({$user->name}).");

                continue;
            }

            $user->update(['membership_expires_at' => $expiresAt]);
            $updated++;
        }

        $this->info("Backfilled membership_expires_at for {$updated} member(s). Skipped: {$noApplication} no approved application, {$noExpiry} could not compute.");

        return self::SUCCESS;
    }
}
