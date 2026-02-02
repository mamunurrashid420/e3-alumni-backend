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
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
            $table->string('applicant_name');
            $table->string('applicant_email')->nullable();
            $table->string('applicant_phone');
            $table->text('applicant_address')->nullable();
            $table->string('class_or_grade')->nullable();
            $table->string('school_name')->nullable();
            $table->string('parent_or_guardian_name')->nullable();
            $table->string('academic_proof_file')->nullable();
            $table->string('other_document_file')->nullable();
            $table->text('statement')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('PENDING');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement("ALTER TABLE scholarship_applications ADD CONSTRAINT scholarship_applications_status_check CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};
