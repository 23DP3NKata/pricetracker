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
        // user_products - frequently filtered by user_id and is_active
        Schema::table('user_products', function (Blueprint $table) {
            $table->index(['user_id', 'is_active']);
            $table->index(['product_id', 'user_id']);
        });

        // notifications - for filtering by user_id and read status
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });

        // products - for searching by status and sorting
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'rank']);
            $table->index('symbol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropIndex(['product_id', 'user_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_read']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'rank']);
            $table->dropIndex('symbol');
        });
    }
};
