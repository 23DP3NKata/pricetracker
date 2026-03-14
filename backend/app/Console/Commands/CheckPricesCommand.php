<?php

namespace App\Console\Commands;

use App\Services\PriceScraperService;
use Illuminate\Console\Command;

class CheckPricesCommand extends Command
{
    protected $signature = 'prices:check {--force : Check all active products regardless of schedule}';
    protected $description = 'Check prices for all products that are due for a check';

    public function handle(PriceScraperService $scraper): int
    {
        $force = (bool) $this->option('force');

        if ($force) {
            $this->info('Force mode: checking ALL active products...');
        } else {
            $this->info('Starting price check...');
        }

        $result = $scraper->checkDuePrices($force);

        $this->info("Done. Checked: {$result['checked']}, Errors: {$result['errors']}");

        if (!empty($result['error_details'])) {
            $this->newLine();
            $this->warn('Error details:');
            foreach ($result['error_details'] as $detail) {
                $this->error("  [{$detail['product_id']}] {$detail['url']}: {$detail['message']}");
            }
        }

        return self::SUCCESS;
    }
}
