<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Member information
            $table->string('member_id')->nullable(); // Member Unique ID Number
            $table->string('name');
            $table->text('address');
            $table->string('mobile_number');

            // Payment details
            $table->string('payment_purpose'); // ASSOCIATE_MEMBERSHIP_FEES, GENERAL_MEMBERSHIP_FEES, etc.
            $table->decimal('payment_amount', 10, 2);
            $table->string('payment_proof_file')->nullable();

            // Status
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
