<?php

namespace App\Console\Commands;

use App\Services\PriceScraperService;
use Illuminate\Console\Command;

class CheckPricesCommand extends Command
{
    protected $signature = 'prices:check';
    protected $description = 'Check prices for all products that are due for a check';

    public function handle(PriceScraperService $scraper): int
    {
        $this->info('Starting price check...');

        $result = $scraper->checkDuePrices();

        $this->info("Done. Checked: {$result['checked']}, Errors: {$result['errors']}");

        return self::SUCCESS;
    }
}
