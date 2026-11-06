<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$controller = (new ReflectionClass(App\Http\Controllers\PushNotificationController::class))->newInstanceWithoutConstructor();
$resolve = new ReflectionMethod($controller, 'notificationUserIdsForUser');
foreach ([21, 22] as $id) {
    $user = new App\Models\User();
    $user->id = $id;
    $user->user_id = 1; // Shared admin/creator must never become a recipient alias.
    $user->login_user_id = 8;
    if ($resolve->invoke($controller, $user) !== [$id]) {
        throw new RuntimeException('Notification access must remain scoped to the login account.');
    }
}
echo "PASS: notification recipient scope excludes creator and profile aliases.\n";
