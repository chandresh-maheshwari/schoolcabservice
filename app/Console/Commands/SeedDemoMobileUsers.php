<?php

namespace App\Console\Commands;

use App\Models\Child;
use App\Models\ChildSubscription;
use App\Models\Driver;
use App\Models\Parents;
use App\Models\Role;
use App\Models\Route;
use App\Models\StopPickup;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedDemoMobileUsers extends Command
{
    protected $signature = 'scb:demo-users
        {--parent-email=rakholiyameet9@gmail.com}
        {--driver-email=meet@cherrypiksoftware.com}
        {--password=Test@1234}
        {--pin=1234}
        {--force-reset-password : Update password even if users exist}';

    protected $description = 'Create demo Parent/Driver users and a sample route/child for mobile testing (shared DB mode).';

    public function handle(): int
    {
        $parentEmail = trim((string) $this->option('parent-email'));
        $driverEmail = trim((string) $this->option('driver-email'));
        $password = (string) $this->option('password');
        $pin = (string) $this->option('pin');

        if ($parentEmail === '' || $driverEmail === '' || $password === '') {
            $this->error('parent-email, driver-email, and password are required.');
            return Command::FAILURE;
        }

        $parentRole = Role::query()->notDeleted()->firstOrCreate(['name' => 'parent']);
        $driverRole = Role::query()->notDeleted()->firstOrCreate(['name' => 'driver']);

        $parentUser = User::query()->where('email', $parentEmail)->first();
        if (! $parentUser) {
            $parentUser = User::create([
                'first_name' => 'Demo',
                'last_name' => 'Parent',
                'mobile' => null,
                'email' => $parentEmail,
                'password' => Hash::make($password),
                'role_id' => $parentRole->id,
            ]);
        } else {
            $parentUser->role_id = $parentRole->id;
            if ((bool) $this->option('force-reset-password')) {
                $parentUser->password = Hash::make($password);
            }
            $parentUser->save();
        }

        $driverUser = User::query()->where('email', $driverEmail)->first();
        if (! $driverUser) {
            $driverUser = User::create([
                'first_name' => 'Demo',
                'last_name' => 'Driver',
                'mobile' => null,
                'email' => $driverEmail,
                'password' => Hash::make($password),
                'role_id' => $driverRole->id,
            ]);
        } else {
            $driverUser->role_id = $driverRole->id;
            if ((bool) $this->option('force-reset-password')) {
                $driverUser->password = Hash::make($password);
            }
            $driverUser->save();
        }

        $parentProfile = Parents::query()
            ->where('login_user_id', $parentUser->id)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->first();

        if (! $parentProfile) {
            $parentProfile = Parents::create([
                'login_user_id' => $parentUser->id,
                'user_id' => $parentUser->id,
                'father_name' => 'Demo Father',
                'mother_name' => 'Demo Mother',
                'email' => $parentEmail,
                'contact_number' => '9999999999',
                'alternative_contact_number' => '8888888888',
                'address_1' => 'Demo Address 1',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'pincode' => '380000',
                'status' => 1,
                'deleted' => 0,
            ]);
        }

        $driverProfile = Driver::query()
            ->where('login_user_id', $driverUser->id)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->first();

        if (! $driverProfile) {
            $driverProfile = Driver::create([
                'login_user_id' => $driverUser->id,
                'user_id' => $driverUser->id,
                'vehicle_id' => null,
                'driver_name' => 'Demo Driver',
                'driver_phone' => '7777777777',
                'emergency_phone' => '6666666666',
                'license_no' => 'DEMO-LIC-' . Str::upper(Str::random(8)),
                'experience_years' => 3,
                'status' => 1,
                'is_assigned' => 1,
                'deleted' => 0,
            ]);
        }

        $route = Route::query()
            ->where('driver_id', $driverProfile->id)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->orderByDesc('id')
            ->first();

        if (! $route) {
            $route = Route::create([
                'user_id' => $driverUser->id,
                'school_id' => null,
                'name' => 'Demo Route (Iscon → Jodhpur)',
                'bus_id' => null,
                'driver_id' => $driverProfile->id,
                'route_json' => [
                    'geojson' => null,
                    'stops' => [],
                ],
            ]);
        }

        $stopIscon = StopPickup::query()
            ->where('route_id', $route->id)
            ->where('sequence_order', 1)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->first();

        if (! $stopIscon) {
            $stopIscon = StopPickup::create([
                'user_id' => $driverUser->id,
                'route_id' => $route->id,
                'pickup_name' => 'Iscon Cross Road',
                'stop_name' => 'Iscon Cross Road',
                'latitude' => 23.0298,
                'longitude' => 72.5053,
                'sequence_order' => 1,
                'status' => 1,
                'deleted' => 0,
            ]);
        }

        $stopJodhpur = StopPickup::query()
            ->where('route_id', $route->id)
            ->where('sequence_order', 2)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->first();

        if (! $stopJodhpur) {
            $stopJodhpur = StopPickup::create([
                'user_id' => $driverUser->id,
                'route_id' => $route->id,
                'pickup_name' => 'Jodhpur',
                'stop_name' => 'Jodhpur',
                'latitude' => 23.0154,
                'longitude' => 72.5101,
                'sequence_order' => 2,
                'status' => 1,
                'deleted' => 0,
            ]);
        }

        $child = Child::query()
            ->where('parent_id', $parentProfile->id)
            ->where('child_name', 'Meet Demo')
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->orderByDesc('id')
            ->first();

        if (! $child) {
            $child = Child::create([
                'user_id' => $parentUser->id,
                'child_name' => 'Meet Demo',
                'parent_id' => $parentProfile->id,
                'school_id' => null,
                'pickup_name' => $stopIscon->id,
                'stop_name' => $stopJodhpur->id,
                'route_id' => $route->id,
                'secret_pin' => $pin !== '' ? $pin : (string) random_int(1000, 9999),
                'gender' => 'Male',
                'date_of_birth' => '2015-01-01',
                'class' => '5',
                'section' => 'A',
                'status' => 1,
                'deleted' => 0,
            ]);
        } else {
            $child->user_id = $parentUser->id;
            $child->route_id = $route->id;
            $child->pickup_name = $stopIscon->id;
            $child->stop_name = $stopJodhpur->id;
            if (empty($child->secret_pin)) {
                $child->secret_pin = $pin !== '' ? $pin : (string) random_int(1000, 9999);
            }
            $child->save();
        }

        // Ensure current subscription is active for vehicle.
        if (class_exists(ChildSubscription::class)) {
            ChildSubscription::query()
                ->where('child_id', $child->id)
                ->where('service_type', 'vehicle')
                ->where('is_current', 1)
                ->update(['is_current' => null]);

            $subscription = ChildSubscription::create([
                'child_id' => $child->id,
                'service_type' => 'vehicle',
                'package_type' => '1month',
                'status' => 'active',
                'source' => 'admin_cash',
                'is_current' => 1,
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'created_by_user_id' => $driverUser->id,
                'notes' => 'Demo cash subscription',
            ]);

            if (class_exists(SubscriptionPayment::class)) {
                SubscriptionPayment::create([
                    'child_subscription_id' => $subscription->id,
                    'channel' => 'cash',
                    'status' => 'paid',
                    'amount' => 0,
                    'currency' => 'INR',
                    'receipt_no' => 'DEMO-REC-001',
                    'paid_at' => now(),
                    'meta' => ['source' => 'scb:demo-users'],
                ]);
            }
        }

        $this->info('Demo users ready (Laravel-side):');
        $this->line("Parent: {$parentEmail} / {$password}");
        $this->line("Driver: {$driverEmail} / {$password}");
        $this->line("Child: {$child->child_name} (PIN {$child->secret_pin}), routeId={$route->id}, childId={$child->id}");

        return Command::SUCCESS;
    }
}
