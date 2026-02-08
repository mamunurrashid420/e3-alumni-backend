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
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('event_at')->nullable()->after('location');
            $table->timestamp('registration_opens_at')->nullable()->after('event_at');
            $table->timestamp('registration_closes_at')->nullable()->after('registration_opens_at');
        });

        DB::table('events')->update([
            'event_at' => DB::raw('start_at'),
            'registration_opens_at' => DB::raw('start_at'),
            'registration_closes_at' => DB::raw('end_at'),
        ]);

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('event_at')->nullable(false)->change();
            $table->timestamp('registration_opens_at')->nullable(false)->change();
            $table->timestamp('registration_closes_at')->nullable(false)->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('start_at')->nullable()->after('location');
            $table->timestamp('end_at')->nullable()->after('start_at');
        });

        DB::table('events')->update([
            'start_at' => DB::raw('event_at'),
            'end_at' => DB::raw('registration_closes_at'),
        ]);

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('start_at')->nullable(false)->change();
            $table->timestamp('end_at')->nullable(false)->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_at', 'registration_opens_at', 'registration_closes_at']);
        });
    }
};
