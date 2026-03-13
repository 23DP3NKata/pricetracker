<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->foreignId('status_changed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->unsignedInteger('monthly_limit')->default(5);
            $table->unsignedInteger('checks_used')->default(0);
            $table->timestamp('last_username_change')->nullable();
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();

            $table->index('status');
        });

        // Required for Sanctum SPA cookie authentication
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Required for Breeze forgot-password flow
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
