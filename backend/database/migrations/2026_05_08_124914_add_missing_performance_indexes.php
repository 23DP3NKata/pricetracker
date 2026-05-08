<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // user_products - frequently filtered by user_id and is_active
        Schema::table('user_products', function (Blueprint $table) {
            if (!$this->indexExists('user_products', 'user_products_user_id_is_active_index')) {
                $table->index(['user_id', 'is_active']);
            }
            if (!$this->indexExists('user_products', 'user_products_product_id_user_id_index')) {
                $table->index(['product_id', 'user_id']);
            }
        });

        // products - for searching by status and ordering
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_status_rank_index')) {
                $table->index(['status', 'rank']);
            }
            if (!$this->indexExists('products', 'products_symbol_index')) {
                $table->index('symbol');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            if ($this->indexExists('user_products', 'user_products_user_id_is_active_index')) {
                $table->dropIndex(['user_id', 'is_active']);
            }
            if ($this->indexExists('user_products', 'user_products_product_id_user_id_index')) {
                $table->dropIndex(['product_id', 'user_id']);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if ($this->indexExists('products', 'products_status_rank_index')) {
                $table->dropIndex(['status', 'rank']);
            }
            if ($this->indexExists('products', 'products_symbol_index')) {
                $table->dropIndex('symbol');
            }
        });
    }

    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_NAME = ? AND INDEX_NAME = ?
        ", [$table, $indexName]);

        return count($indexes) > 0;
    }
};
