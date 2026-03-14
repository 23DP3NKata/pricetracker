<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\PriceHistory;
use Illuminate\Console\Command;

class CleanupTrackingDataCommand extends Command
{
    protected $signature = 'prices:cleanup
        {--notifications-days=60 : Delete notifications older than this many days}
        {--history-days=180 : Delete price history entries older than this many days}
        {--delete-unread : Also delete unread notifications older than the notification threshold}';

    protected $description = 'Clean up old tracking notifications and price history records';

    public function handle(): int
    {
        $notificationDays = max(1, (int) $this->option('notifications-days'));
        $historyDays = max(1, (int) $this->option('history-days'));

        $notificationsQuery = Notification::where('created_at', '<', now()->subDays($notificationDays));

        if (!$this->option('delete-unread')) {
            $notificationsQuery->where('is_read', true);
        }

        $deletedNotifications = $notificationsQuery->delete();

        $deletedPriceHistory = PriceHistory::where('checked_at', '<', now()->subDays($historyDays))->delete();

        $this->info("Deleted {$deletedNotifications} notifications.");
        $this->info("Deleted {$deletedPriceHistory} price history records.");

        return self::SUCCESS;
    }
}