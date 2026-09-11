<?php

// Run with: php tests/regression/driver_school_delete.php
// Uses SQLite in memory; never deletes application records.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection('sqlite');
$db->statement('CREATE TABLE drivers (id INTEGER PRIMARY KEY, user_id INTEGER, school_id INTEGER, vehicle_id INTEGER, deleted INTEGER, is_assigned INTEGER, updated_at TEXT, created_at TEXT)');
$db->statement('CREATE TABLE vehicles (id INTEGER PRIMARY KEY, user_id INTEGER, school_id INTEGER, driver_id INTEGER, is_assigned INTEGER, updated_at TEXT, created_at TEXT)');
$user = new class extends App\Models\User {
    public function isAdmin(): bool { return false; }
    public function isSchool(): bool { return true; }
};
$user->id = 99;
Illuminate\Support\Facades\Auth::setUser($user);
$request = Illuminate\Http\Request::create('/api/driver/55', 'DELETE');
$request->attributes->set('current_school', (object) ['id' => 10, 'user_id' => 99]);
$app->instance('request', $request);
foreach ([55 => 10, 56 => 10, 57 => 20, 58 => 10] as $id => $school) {
    // Created by admin user 1, then explicitly assigned to a school.
    $db->table('drivers')->insert(['id' => $id, 'user_id' => 1, 'school_id' => $school,
        'vehicle_id' => $id, 'deleted' => 0, 'is_assigned' => 1]);
    $db->table('vehicles')->insert(['id' => $id, 'user_id' => 1, 'school_id' => $school,
        'driver_id' => $id, 'is_assigned' => 1]);
}
$controller = new App\Http\Controllers\DriverController();
foreach ([55, 58] as $id) {
    $response = $id === 55 ? $controller->destroy($id) : $controller->destroy('school-slug', $id);
    if (!$response->getData(true)['success']) throw new RuntimeException('Single delete failed');
    $driver = $db->table('drivers')->find($id);
    $vehicle = $db->table('vehicles')->find($id);
    if ($driver->deleted !== 1 || $driver->vehicle_id !== null || $vehicle->driver_id !== null || $vehicle->is_assigned !== 0) {
        throw new RuntimeException('Single delete did not release assigned vehicle');
    }
}
try {
    $controller->destroy(57);
    throw new RuntimeException('Other school driver was accessible');
} catch (Illuminate\Database\Eloquent\ModelNotFoundException $expected) {
    // Other schools must remain outside the delete scope.
}
$request->merge(['ids' => [56, 57]]);
$controller->multiDelete($request);
if ($db->table('drivers')->where('id', 56)->value('deleted') !== 1
    || $db->table('vehicles')->where('id', 56)->value('driver_id') !== null
    || $db->table('drivers')->where('id', 57)->value('deleted') !== 0
    || $db->table('vehicles')->where('id', 57)->value('driver_id') !== 57) {
    throw new RuntimeException('Bulk delete scope or vehicle cleanup failed');
}
echo "PASS: API and school routes delete assigned drivers; bulk delete releases vehicles and preserves other schools\n";
