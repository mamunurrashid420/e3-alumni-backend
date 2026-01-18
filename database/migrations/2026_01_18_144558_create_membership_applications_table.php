<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('membership_type'); // GENERAL, LIFETIME, ASSOCIATE
            $table->string('full_name');
            $table->string('name_bangla');
            $table->string('father_name');
            $table->string('mother_name')->nullable();
            $table->string('gender'); // MALE, FEMALE, OTHER

            // Education
            $table->integer('jsc_year')->nullable();
            $table->integer('ssc_year')->nullable();
            $table->string('studentship_proof_type')->nullable(); // JSC, EIGHT, SSC, METRIC_CERTIFICATE, MARK_SHEET, OTHERS
            $table->string('studentship_proof_file')->nullable();
            $table->string('highest_educational_degree')->nullable();

            // Contact
            $table->text('present_address');
            $table->text('permanent_address');
            $table->string('email')->nullable();
            $table->string('mobile_number');

            // Professional
            $table->string('profession');
            $table->string('designation')->nullable();
            $table->string('institute_name')->nullable();

            // Membership details
            $table->string('t_shirt_size'); // XXXL, XXL, XL, L, M, S
            $table->string('blood_group'); // A+, A-, B+, B-, AB+, AB-, O+, O-
            $table->decimal('entry_fee', 10, 2)->nullable();
            $table->decimal('yearly_fee', 10, 2);
            $table->integer('payment_years'); // 1, 2, or 3
            $table->decimal('total_paid_amount', 10, 2);
            $table->string('receipt_file')->nullable();

            // Status
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });

        // Add check constraints (PostgreSQL only)
        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement("ALTER TABLE membership_applications ADD CONSTRAINT membership_applications_membership_type_check CHECK (membership_type IN ('GENERAL', 'LIFETIME', 'ASSOCIATE'))");
            \DB::statement("ALTER TABLE membership_applications ADD CONSTRAINT membership_applications_gender_check CHECK (gender IN ('MALE', 'FEMALE', 'OTHER'))");
            \DB::statement("ALTER TABLE membership_applications ADD CONSTRAINT membership_applications_status_check CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
