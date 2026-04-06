<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->index('user_id', 'user_products_user_id_index');
            $table->index('product_id', 'user_products_product_id_index');
            $table->dropUnique('user_products_user_id_product_id_unique');
            $table->index(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_products', function (Blueprint $table) {
            $table->dropIndex('user_products_user_id_product_id_index');
            $table->dropIndex('user_products_product_id_index');
            $table->dropIndex('user_products_user_id_index');
            $table->unique(['user_id', 'product_id']);
        });
    }
};
