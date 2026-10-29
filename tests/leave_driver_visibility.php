<?php

require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\PushNotificationController;
use App\Models\MobileNotification;
use App\Models\User;

$controller = (new ReflectionClass(PushNotificationController::class))->newInstanceWithoutConstructor();
$visible = new ReflectionMethod(PushNotificationController::class, 'mobileNotificationVisibleToUser');
$visible->setAccessible(true);
$driver = new User;
$driver->id = 45;
$notification = new MobileNotification;
$notification->user_id = 45;
$notification->type = 'child_leave';
if ($visible->invoke($controller, $notification, $driver) !== true) {
    throw new RuntimeException('Assigned driver must see child leave notifications.');
}
$driver->id = 46;
if ($visible->invoke($controller, $notification, $driver) !== false) {
    throw new RuntimeException('Another account must not see this notification.');
}
echo "PASS: child leave visible to addressed driver only.\n";
