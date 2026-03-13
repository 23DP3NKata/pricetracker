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
            $table->string('url', 500)->unique();
            $table->string('image_url', 500)->nullable();
            $table->decimal('current_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('EUR');
            $table->string('store_name', 100)->nullable();
            $table->enum('status', ['active', 'hidden', 'deleted'])->default('active');
            $table->unsignedInteger('tracking_count')->default(0);
            $table->unsignedInteger('checks_count')->default(0);
            $table->timestamp('last_successful_check')->nullable();
            $table->unsignedInteger('consecutive_errors')->default(0);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
