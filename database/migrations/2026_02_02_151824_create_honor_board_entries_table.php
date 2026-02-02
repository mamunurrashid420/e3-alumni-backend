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
        Schema::create('honor_board_entries', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // President, GeneralSecretary
            $table->string('name');
            $table->string('member_id')->nullable();
            $table->string('durations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('honor_board_entries');
    }
};
