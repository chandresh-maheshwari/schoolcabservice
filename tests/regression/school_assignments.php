<?php

// Run with: php tests/regression/school_assignments.php
// All fixtures and writes use an isolated, in-memory SQLite database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection('sqlite');
$db->statement('CREATE TABLE schools (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, school_name TEXT, deleted INTEGER)');
foreach (['drivers', 'vehicles', 'routes', 'vehicle_types', 'emergency_types', 'driver_vehicle_histories'] as $table) {
    $db->statement("CREATE TABLE $table (id INTEGER PRIMARY KEY, user_id INTEGER, school_id INTEGER, driver_id INTEGER, vehicle_id INTEGER, vehicle_type_id INTEGER, deleted INTEGER, status INTEGER, driver_name TEXT, vehicle_number TEXT, is_assigned INTEGER)");
    foreach ([1 => 1, 2 => null] as $id => $schoolId) {
        $db->table($table)->insert(['id' => $id, 'user_id' => 99, 'school_id' => $schoolId,
            'driver_id' => $id, 'vehicle_id' => $id, 'deleted' => 0, 'status' => 1]);
    }
}
foreach (['emergency_incidents', 'ratings'] as $table) {
    $db->statement("CREATE TABLE $table (id INTEGER PRIMARY KEY, user_id INTEGER, driver_id INTEGER, vehicle_id INTEGER, deleted INTEGER, status INTEGER)");
    foreach ([1, 2] as $id) {
        $db->table($table)->insert(['id' => $id, 'user_id' => 99, 'driver_id' => $id, 'vehicle_id' => $id, 'deleted' => 0, 'status' => 1]);
    }
}
$db->table('schools')->insert(['id' => 1, 'user_id' => 99, 'school_name' => 'Original school', 'deleted' => 0]);
$user = new class extends App\Models\User {
    public function isAdmin(): bool { return true; }
    public function isSchool(): bool { return false; }
};
Illuminate\Support\Facades\Auth::setUser($user);
$helper = new class extends App\Http\Controllers\Controller {
    public function names(string $table): array { return $this->getAssignedSchoolNameMap($table, [1, 2]); }
    public function assignedSchool(Illuminate\Http\Request $request, ?int $saved = null): ?int {
        return $this->resolveModuleSchoolId($request, $saved, [2], 99);
    }
};
$listings = [
    [new App\Http\Controllers\DriverController(), 'driverList'],
    [$app->make(App\Http\Controllers\EmergencyController::class), 'emergencyList'],
    [$app->make(App\Http\Controllers\RatingController::class), 'ratingList'],
    [new App\Http\Controllers\DriverVehicleHistoryController(), 'driverHistoryList'],
];
$check = function (array $expected) use ($helper, $listings) {
    foreach (['drivers', 'vehicles', 'routes', 'vehicle_types', 'emergency_types', 'driver_vehicle_histories'] as $table) {
        $map = $helper->names($table);
        if ([$map[1] ?? '-', $map[2] ?? '-'] !== $expected) throw new RuntimeException("Incorrect school mapping: $table");
    }
    foreach ($listings as [$controller, $method]) {
        foreach (['id', 'school_name'] as $sort) {
            $request = Illuminate\Http\Request::create('/', 'POST', ['iDisplayStart' => 0, 'iDisplayLength' => 10,
                'iSortCol_0' => 0, 'mDataProp_0' => $sort, 'sSortDir_0' => 'asc']);
            $result = $controller->$method($request)->getData(true);
            $rows = $result['data'] ?? $result['aaData'];
            $names = array_column($rows, 'school_name', 'id');
            if (count($rows) !== 2 || [$names[1], $names[2]] !== $expected) throw new RuntimeException("Incorrect listing/sort: $method/$sort");
        }
    }
};
$check(['Original school', '-']);
$db->table('schools')->where('id', 1)->update(['deleted' => 1]);
$check(['-', '-']);
// Deliberately reuse the creator account: it must never imply reassignment.
$db->table('schools')->insert(['id' => 2, 'user_id' => 99, 'school_name' => 'New school', 'deleted' => 0]);
$check(['-', '-']);
foreach (['drivers', 'vehicles', 'routes', 'vehicle_types', 'emergency_types', 'driver_vehicle_histories'] as $table) {
    $db->table($table)->where('id', 1)->update(['school_id' => 2]);
}
$check(['New school', '-']);
if ($helper->assignedSchool(Illuminate\Http\Request::create('/', 'POST', ['school_id' => ''])) !== null
    || $helper->assignedSchool(Illuminate\Http\Request::create('/'), 1) !== 1
    || $helper->assignedSchool(Illuminate\Http\Request::create('/', 'POST', ['school_id' => 2]), 1) !== 2) {
    throw new RuntimeException('Admin school assignment inferred instead of submitted/preserved');
}
echo "PASS: school deletion, new school with same creator, unassigned records, explicit reassignment, listing sorting, admin save semantics\n";
