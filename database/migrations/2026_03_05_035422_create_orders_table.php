<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('sellers');
            $table->unsignedBigInteger('address_id')->nullable();
            $table->enum('status', [
                'pending','processing','shipped','delivered','cancelled','refunded'
            ])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('payment_method', ['card','cash_on_delivery','wallet'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending','paid','failed','refunded'])->default('pending');
            $table->string('payment_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
