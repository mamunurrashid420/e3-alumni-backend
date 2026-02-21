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
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->text('guest_details')->nullable()->after('guest_count');
            $table->decimal('subscription_amount', 10, 2)->nullable()->after('guest_details');
            $table->decimal('total_subscription', 10, 2)->nullable()->after('subscription_amount');
            $table->string('payment_document_path')->nullable()->after('total_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'guest_details',
                'subscription_amount',
                'total_subscription',
                'payment_document_path',
            ]);
        });
    }
};
