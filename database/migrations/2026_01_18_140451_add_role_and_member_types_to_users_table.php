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
            $table->string('role')->default('member');
            $table->string('primary_member_type')->nullable();
            $table->foreignId('secondary_member_type_id')->nullable()
                ->constrained('member_types')->nullOnDelete();
        });

        // Update existing users to have 'member' role
        DB::table('users')->update(['role' => 'member']);

        // Add check constraints for enums
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin', 'member'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_primary_member_type_check CHECK (primary_member_type IN ('GENERAL', 'LIFETIME', 'ASSOCIATE') OR primary_member_type IS NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['secondary_member_type_id']);
            $table->dropColumn(['role', 'primary_member_type', 'secondary_member_type_id']);
        });

        // Drop check constraints
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_primary_member_type_check');
    }
};
