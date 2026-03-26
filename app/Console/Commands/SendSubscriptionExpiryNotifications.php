<?php

namespace App\Console\Commands;

use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendSubscriptionExpiryNotifications extends Command
{
    protected $signature = 'push:send-subscription-expiry-notifications';

    protected $description = 'Send automated push notifications for subscriptions that are expiring soon or already expired';

    public function handle(PushNotificationService $pushNotifications): int
    {
        $result = $pushNotifications->sendSubscriptionExpiryNotifications();

        $this->info(sprintf(
            'Processed %d subscriptions and sent %d notifications.',
            (int) ($result['processed'] ?? 0),
            (int) ($result['sent'] ?? 0)
        ));

        return self::SUCCESS;
    }
}
