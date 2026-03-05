<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('slug')->unique();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->string('sku')->nullable()->unique();
            $table->integer('quantity')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['draft','pending','approved','rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
