<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'rank')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedSmallInteger('rank')->nullable()->after('trend');
                $table->index('rank');
            });
        }

        $symbols = ['BTC', 'ETH', 'USDT', 'XRP', 'TRX', 'DOGE', 'BNB', 'USDC', 'SOL', 'LTC'];

        DB::table('products')
            ->where(function ($query) use ($symbols) {
                $query->whereNull('symbol')
                    ->orWhereNotIn('symbol', $symbols);
            })
            ->delete();

        foreach ($symbols as $index => $symbol) {
            DB::table('products')
                ->where('symbol', $symbol)
                ->update(['rank' => $index + 1]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'rank')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['rank']);
                $table->dropColumn('rank');
            });
        }
    }
};
