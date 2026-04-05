<?php

namespace App\Console\Commands;

use App\Services\CoinGeckoPriceService;
use Illuminate\Console\Command;

class CheckPricesCommand extends Command
{
    protected $signature = 'prices:check {--force : Check all active products regardless of schedule}';
    protected $description = 'Check prices for all products that are due for a check';

    public function handle(CoinGeckoPriceService $priceService): int
    {
        $force = (bool) $this->option('force');

        $this->info('Syncing top market assets...');
        $sync = $priceService->syncDefaultTopAssets();
        $this->info("Top assets synced: {$sync['synced']}, Errors: {$sync['errors']}");

        if ($force) {
            $this->info('Force mode: checking ALL active products...');
        } else {
            $this->info('Starting price check...');
        }

        $result = $priceService->checkDuePrices($force);

        $this->info("Done. Checked: {$result['checked']}, Errors: {$result['errors']}");

        if (!empty($result['error_details'])) {
            $this->newLine();
            $this->warn('Error details:');
            foreach ($result['error_details'] as $detail) {
                $target = $detail['symbol'] ?? ('#' . $detail['product_id']);
                $this->error("  [{$detail['product_id']}] {$target}: {$detail['message']}");
            }
        }

        return self::SUCCESS;
    }
}
