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
        Schema::table('convening_committee_members', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('occupation');
        });
        Schema::table('advisory_body_members', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('occupation');
        });
        Schema::table('honor_board_entries', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('durations');
        });
        Schema::table('batch_representatives', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('ssc_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('convening_committee_members', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
        Schema::table('advisory_body_members', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
        Schema::table('honor_board_entries', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
        Schema::table('batch_representatives', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
