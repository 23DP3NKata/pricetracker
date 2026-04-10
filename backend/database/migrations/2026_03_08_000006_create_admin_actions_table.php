<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action_type', [
                'block_user', 'unblock_user', 'delete_user', 'restore_user',
                'hide_product', 'restore_product',
                'change_user_role', 'promote_user', 'demote_user', 'change_user_limit',
            ]);
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
