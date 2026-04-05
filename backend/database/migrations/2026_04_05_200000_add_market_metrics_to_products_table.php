<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'price_change_24h')) {
                $table->decimal('price_change_24h', 10, 4)->nullable()->after('current_price');
            }

            if (!Schema::hasColumn('products', 'trend')) {
                $table->enum('trend', ['up', 'down', 'flat'])->default('flat')->after('price_change_24h');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'trend')) {
                $table->dropColumn('trend');
            }

            if (Schema::hasColumn('products', 'price_change_24h')) {
                $table->dropColumn('price_change_24h');
            }
        });
    }
};
