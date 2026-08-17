<?php

namespace App\Support;

class PermissionName
{
    public static function normalize(?string $routeName): ?string
    {
        $name = trim((string) $routeName);
        if ($name === '') {
            return null;
        }

        if (str_starts_with($name, 'api.')) {
            $name = substr($name, 4);
        }

        $alwaysAllowed = [
            'logout',
            'logout.user',
            'refreshToken',
            'sendOtp',
            'verifyOtp',
            'resetnewPassword',
            'admin_layout.index',
            'admin.dashboard.live-summary',
            'admin.profile',
            'profile.edit',
            'profile.update',
            'school.dashboard',
            'school.dashboard.live-summary',
            'school.profile.edit',
            'school.profile.update',
            'pushNotifications.index',
            'pushNotifications.send',
            'pushNotifications.settings',
            'pushNotifications.destroy',
            'school.pushNotifications.index',
            'school.pushNotifications.send',
            'school.pushNotifications.settings',
            'school.pushNotifications.destroy',
            'routes.google-preview',
            'school.routes.google-preview',
            'routes.vehicleDrivers',
            'school.routes.vehicleDrivers',
            'stopPickup.route-points',
            'school.stopPickup.route-points',
        ];

        if (in_array($name, $alwaysAllowed, true)) {
            return null;
        }

        $exactNameMap = [
            'vehicle.tracking.live' => 'vehicle.tracking',
            'vehicle.tracking.debug' => 'vehicle.tracking',
            'vehicle.tracking.update' => 'vehicle.tracking',
            'school.vehicle.tracking.live' => 'vehicle.tracking',
            'school.vehicle.tracking.debug' => 'vehicle.tracking',
            'school.vehicle.tracking.update' => 'vehicle.tracking',
            'routes.customLocations.search' => 'routes.create',
            'routes.customLocations.store' => 'routes.create',
            'school.routes.customLocations.search' => 'routes.create',
            'school.routes.customLocations.store' => 'routes.create',
            'subscriptions.cash.create' => 'child.create',
            'school.subscriptions.cash.create' => 'child.create',
            'subscriptions.cash' => 'child.create',
            'subscriptions.current' => 'child.create',
            'rolelist' => 'roles.index',
            'userlist' => 'users.index',
            'toggle-user-status' => 'users.update',
            'User.Edit' => 'users.edit',
            'users.showEncoded' => 'users.show',
            'users.content-counts' => 'users.destroy',
        ];

        if (isset($exactNameMap[$name])) {
            return $exactNameMap[$name];
        }

        if (str_starts_with($name, 'school.')) {
            $parts = explode('.', $name);
            $schoolPanelModules = [
                'vehicleType',
                'emergencyType',
                'vehicle',
                'driver',
                'school',
                'routes',
                'packageDetails',
                'booking',
                'emergency',
                'rating',
                'stopPickup',
                'driverHistoryList',
                'parent',
                'child',
                'profile',
                'subscriptions',
                'leaveRequests',
                'supportRequests',
                'pushNotifications',
            ];

            if (count($parts) >= 3 && in_array($parts[1], $schoolPanelModules, true)) {
                $name = implode('.', array_slice($parts, 1));
            }
        }

        $parts = explode('.', $name);
        if (count($parts) < 2) {
            return $name;
        }

        $actionIndex = count($parts) - 1;
        $action = strtolower((string) $parts[$actionIndex]);
        $actionMap = [
            'list' => 'index',
            'deleted-list' => 'trash',
            'loginas' => 'update',
            'multi-delete' => 'destroy',
            'togglestatus' => 'update',
            'toggle-status' => 'update',
            'update-photo' => 'update',
            'deleteimage' => 'update',
            'vehicleimage' => 'update',
            'rcimage' => 'update',
            'insuranceimage' => 'update',
            'licenseimage' => 'update',
            'adharcardimage' => 'update',
            'childimage' => 'update',
            'childadhaarimage' => 'update',
            'aboutimage' => 'update',
            'changepassword' => 'update',
            'delete' => 'destroy',
            'delete-all' => 'destroy',
            'force-delete' => 'destroy',
            'showencoded' => 'show',
            'setparent' => 'update',
            'review' => 'update',
            'regenerate-pin' => 'update',
            'regeneratepin' => 'update',
        ];

        if (isset($actionMap[$action])) {
            $parts[$actionIndex] = $actionMap[$action];
        }

        // Image/document cleanup endpoints should reuse the module's update permission.
        // Example: `benefitSection.benefitImage` -> `benefitSection.update`
        if (preg_match('/(image|photo)$/', $action)) {
            $parts[$actionIndex] = 'update';
        }

        return implode('.', $parts);
    }

    public static function hiddenPermissionNames(): array
    {
        return [
            'booking.index',
            'booking.create',
            'booking.store',
            'booking.show',
            'booking.edit',
            'booking.update',
            'booking.destroy',
            'logout.user',
            'admin_layout.index',
            'admin.profile',
            'profile.edit',
            'profile.update',
            'school.dashboard',
            'school.profile.edit',
            'school.profile.update',
            'pushNotifications.index',
            'pushNotifications.send',
            'pushNotifications.settings',
            'pushNotifications.destroy',
            'school.pushNotifications.index',
            'school.pushNotifications.send',
            'school.pushNotifications.settings',
            'school.pushNotifications.destroy',
            'User.Edit',
            'users.showEncoded',
            'export.download',
        ];
    }
}
