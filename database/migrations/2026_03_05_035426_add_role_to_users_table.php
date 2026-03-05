<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // role is now in the users table directly (already added in create_users_table)
        // This migration is intentionally left as a no-op to preserve migration order
    }

    public function down(): void
    {
        //
    }
};
