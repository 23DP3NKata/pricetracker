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
            $table->string('title');
            $table->string('symbol', 20)->nullable();
            $table->string('coingecko_id', 120)->nullable();
            $table->string('product_page_url', 500)->nullable();
            $table->string('canonical_url', 500)->unique();
            $table->string('image_url', 500)->nullable();
            $table->decimal('current_price', 20, 8)->nullable();
            $table->decimal('price_change_24h', 10, 4)->nullable();
            $table->enum('trend', ['up', 'down', 'flat'])->default('flat');
            $table->string('currency', 10)->default('EUR');
            $table->enum('status', ['active', 'hidden'])->default('active');
            $table->unsignedInteger('tracking_count')->default(0);
            $table->unsignedInteger('checks_count')->default(0);
            $table->timestamp('last_successful_check')->nullable();
            $table->unsignedInteger('consecutive_errors')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('symbol');
            $table->index('coingecko_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
