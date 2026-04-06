<?php

namespace App\Console\Commands;

use App\Services\CoinGeckoPriceService;
use Illuminate\Console\Command;

class CheckPricesCommand extends Command
{
    protected $signature = 'prices:check
        {--force : Check all active products regardless of schedule}
        {--sync-top : Sync default top assets before checking prices}
        {--skip-check : Only sync top assets and skip tracker price checks}';
    protected $description = 'Check prices for all products that are due for a check';

    public function handle(CoinGeckoPriceService $priceService): int
    {
        $force = (bool) $this->option('force');
        $syncTop = (bool) $this->option('sync-top');
        $skipCheck = (bool) $this->option('skip-check');

        if ($skipCheck && !$syncTop) {
            $this->warn('Option --skip-check requires --sync-top. Nothing to execute.');
            return self::SUCCESS;
        }

        $sync = [
            'synced' => 0,
            'errors' => 0,
            'product_ids' => [],
        ];

        if ($syncTop) {
            $this->info('Syncing top market assets...');
            $sync = $priceService->syncDefaultTopAssets();
            $this->info("Top assets synced: {$sync['synced']}, Errors: {$sync['errors']}");
        }

        if ($skipCheck) {
            return self::SUCCESS;
        }

        if ($force) {
            $this->info('Force mode: checking ALL active products...');
        } else {
            $this->info('Starting price check...');
        }

        $result = $priceService->checkDuePrices($force, $sync['product_ids'] ?? []);

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
