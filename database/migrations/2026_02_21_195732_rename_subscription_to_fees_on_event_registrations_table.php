<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->decimal('participant_fee', 10, 2)->nullable()->after('guest_details');
            $table->decimal('total_fees', 10, 2)->nullable()->after('participant_fee');
        });

        DB::table('event_registrations')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('event_registrations')
                    ->where('id', $row->id)
                    ->update([
                        'participant_fee' => $row->subscription_amount,
                        'total_fees' => $row->total_subscription,
                    ]);
            }
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['subscription_amount', 'total_subscription']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->decimal('subscription_amount', 10, 2)->nullable()->after('guest_details');
            $table->decimal('total_subscription', 10, 2)->nullable()->after('subscription_amount');
        });

        DB::table('event_registrations')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('event_registrations')
                    ->where('id', $row->id)
                    ->update([
                        'subscription_amount' => $row->participant_fee,
                        'total_subscription' => $row->total_fees,
                    ]);
            }
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['participant_fee', 'total_fees']);
        });
    }
};
