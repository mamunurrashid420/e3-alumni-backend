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
        Schema::table('users', function (Blueprint $table) {
            // Add phone column (nullable, unique)
            $table->string('phone')->nullable()->unique()->after('email');
        });

        // Make email nullable using raw SQL (more reliable across database drivers)
        DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove phone column
            $table->dropUnique(['phone']);
            $table->dropColumn('phone');
        });

        // Make email required again using raw SQL (this might fail if there are NULL emails)
        DB::statement('ALTER TABLE users ALTER COLUMN email SET NOT NULL');
    }
};
