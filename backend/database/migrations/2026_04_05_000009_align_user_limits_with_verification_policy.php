<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY monthly_limit INT UNSIGNED NOT NULL DEFAULT 0');
        }

        $verifiedLimit = (int) env('VERIFIED_USER_MONTHLY_LIMIT', 15);

        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['monthly_limit' => 0]);

        DB::table('users')
            ->whereNotNull('email_verified_at')
            ->update(['monthly_limit' => $verifiedLimit]);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY monthly_limit INT UNSIGNED NOT NULL DEFAULT 5');
        }

        DB::table('users')
            ->where('monthly_limit', 0)
            ->update(['monthly_limit' => 5]);
    }
};
