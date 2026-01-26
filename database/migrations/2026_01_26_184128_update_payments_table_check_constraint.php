<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop the check constraints to rely on application-level validation
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_purpose_check');
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Re-add constraints if needed (optional, keeping original values)
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_purpose_check CHECK (payment_purpose IN (
                'ASSOCIATE_MEMBERSHIP_FEES', 
                'GENERAL_MEMBERSHIP_FEES', 
                'LIFETIME_MEMBERSHIP_FEES', 
                'SPECIAL_YEARLY_CONTRIBUTION_EXECUTIVE', 
                'YEARLY_SUBSCRIPTION_ASSOCIATE_MEMBER', 
                'YEARLY_SUBSCRIPTION_GENERAL_MEMBER', 
                'YEARLY_SUBSCRIPTION_LIFETIME_MEMBER', 
                'DONATIONS', 
                'PATRON', 
                'OTHERS'
            ))");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED'))");
        }
    }
};
