<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyRequestsCommand extends Command
{
    protected $signature = 'users:reset-monthly-usage';

    protected $description = 'Reset checks_used counter for all users once per month';

    public function handle(): int
    {
        $updated = User::query()->update(['checks_used' => 0]);

        $this->info("Monthly request usage reset for {$updated} users.");

        return self::SUCCESS;
    }
}
