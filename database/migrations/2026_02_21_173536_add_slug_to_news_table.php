<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('id')->unique();
        });

        foreach (\App\Models\News::all() as $news) {
            $base = Str::slug($news->title);
            $slug = $base;
            $n = 0;
            while (\App\Models\News::where('slug', $slug)->where('id', '!=', $news->id)->exists()) {
                $n++;
                $slug = $base.'-'.$n;
            }
            $news->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
