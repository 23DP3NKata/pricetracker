<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 20, 8);
            $table->timestamp('checked_at')->useCurrent();

            $table->index(['product_id', 'checked_at']);
            $table->index('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
