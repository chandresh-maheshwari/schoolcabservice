<?php

namespace App\Console\Commands;

use App\Models\MobileNotification;
use Illuminate\Console\Command;

class PruneMobileNotifications extends Command
{
    protected $signature = 'push:prune-mobile-notifications';

    protected $description = 'Delete mobile notifications older than two days';

    public function handle(): int
    {
        $deleted = MobileNotification::pruneExpiredRecords();

        $this->info(sprintf(
            'Deleted %d mobile notifications older than 2 days.',
            $deleted
        ));

        return self::SUCCESS;
    }
}
