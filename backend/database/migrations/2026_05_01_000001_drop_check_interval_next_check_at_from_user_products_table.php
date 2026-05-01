<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            // Legacy interval defaults (1440 vs 5) are no longer relevant after removal.
            $table->dropIndex('user_products_next_check_at_is_active_index');
            $table->dropColumn(['check_interval', 'next_check_at']);
        });
    }

    public function down(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->unsignedInteger('check_interval')->default(1440)->after('product_id');
            $table->timestamp('next_check_at')->nullable()->after('last_checked_at');
            $table->index(['next_check_at', 'is_active']);
        });
    }
};
