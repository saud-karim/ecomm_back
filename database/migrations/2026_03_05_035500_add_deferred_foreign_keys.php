<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add all foreign key constraints that could not be set at table creation time
 * due to migration ordering (tables referenced did not exist yet).
 *
 * Runs last — after ALL tables are created.
 */
return new class extends Migration
{
    public function up(): void
    {
        // orders.address_id → addresses
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('address_id')
                  ->references('id')->on('addresses')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
        });
    }
};
