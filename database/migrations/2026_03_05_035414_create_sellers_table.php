<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('store_name_en');
            $table->string('store_name_ar')->nullable();
            $table->string('store_slug')->unique();
            $table->string('store_logo')->nullable();
            $table->string('store_banner')->nullable();
            $table->text('store_description_en')->nullable();
            $table->text('store_description_ar')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
