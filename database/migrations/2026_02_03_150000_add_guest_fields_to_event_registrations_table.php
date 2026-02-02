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
        if (Schema::hasColumn('event_registrations', 'name')) {
            return;
        }

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('name');
            $table->text('address')->nullable()->after('phone');
            $table->string('ssc_jsc')->nullable()->after('address');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE event_registrations ALTER COLUMN user_id DROP NOT NULL');

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('event_registrations')->whereNull('user_id')->delete();

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE event_registrations ALTER COLUMN user_id SET NOT NULL');

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone', 'address', 'ssc_jsc']);
        });
    }
};
