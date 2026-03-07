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
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('id');
            $table->string('title_ar')->nullable()->after('title_en');
            $table->string('subtitle_en')->nullable()->after('title_ar');
            $table->string('subtitle_ar')->nullable()->after('subtitle_en');
            $table->dropColumn('title');
            $table->dropColumn('subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->dropColumn(['title_en', 'title_ar', 'subtitle_en', 'subtitle_ar']);
        });
    }
};
