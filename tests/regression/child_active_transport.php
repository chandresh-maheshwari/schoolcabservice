<?php

// Isolated database: does not modify application records.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection();
$db->statement('CREATE TABLE routes (id INTEGER PRIMARY KEY, user_id INTEGER, school_id INTEGER, name TEXT, route_json TEXT, deleted INTEGER)');
$db->statement('CREATE TABLE stops_pickup (id INTEGER PRIMARY KEY, user_id INTEGER, school_id INTEGER, route_id INTEGER, pickup_name TEXT, stop_name TEXT, latitude REAL, longitude REAL, sequence_order INTEGER, status INTEGER, deleted INTEGER, created_at TEXT, updated_at TEXT)');
$user = new class extends App\Models\User {
    public function isAdmin(): bool { return true; }
    public function isSchool(): bool { return false; }
};
$user->id = 1;
Illuminate\Support\Facades\Auth::setUser($user);
$request = Illuminate\Http\Request::create('/child');
$app->instance('request', $request);
$route = App\Models\Route::create([
    'id' => 1, 'user_id' => 1, 'school_id' => 10, 'name' => 'Route', 'deleted' => 0,
    'route_json' => ['pickup_points' => [
        ['name' => 'Active pickup', 'lat' => 23, 'lng' => 72],
        ['name' => 'Inactive pickup', 'lat' => 24, 'lng' => 73],
    ], 'end_point' => ['name' => 'Stop', 'lat' => 25, 'lng' => 74]],
]);
foreach ([1 => 1, 2 => 0] as $id => $status) {
    $db->table('stops_pickup')->insert([
        'id' => $id, 'user_id' => 1, 'school_id' => 10, 'route_id' => $route->id,
        'pickup_name' => $id === 1 ? 'Active pickup' : 'Inactive pickup',
        'stop_name' => 'Stop', 'latitude' => 22 + $id, 'longitude' => 71 + $id,
        'status' => $status, 'deleted' => 0, 'sequence_order' => $id,
    ]);
}
$invoke = static function ($controller, $method, $request) {
    return (new ReflectionMethod($controller, $method))->invoke($controller, $request);
};
$child = new App\Http\Controllers\ChildController();
foreach ([[$child, 'getAccessibleStopPickupOptions'],
    [new App\Http\Controllers\ParentController(), 'getAccessibleStopPickupOptionsForChildFlow']] as [$controller, $method]) {
    $options = $invoke($controller, $method, $request);
    if ($options->pluck('id')->all() !== [1]) {
        throw new RuntimeException('Only active pickup must be offered.');
    }
    if ((int) $db->table('stops_pickup')->where('id', 1)->value('status') !== 1) {
        throw new RuntimeException('Opening a child form must preserve active status.');
    }
}
$request->merge(['route_id' => $route->id, 'pickup_name' => '1', 'stop_name' => '1']);
$invoke($child, 'ensureAccessibleTransportSelections', $request);
foreach (['pickup_name', 'stop_name'] as $field) {
    $request->merge(['pickup_name' => '1', 'stop_name' => '1', $field => '2']);
    try {
        $invoke($child, 'ensureAccessibleTransportSelections', $request);
        throw new RuntimeException('Inactive submitted point must be rejected.');
    } catch (Illuminate\Validation\ValidationException $expected) {
        if (!isset($expected->errors()[$field])) throw $expected;
    }
}
$db->table('stops_pickup')->update(['status' => 0]);
$request->replace(['route_id' => $route->id]);
try {
    $invoke($child, 'fillRouteTransportSelections', $request);
    throw new RuntimeException('Fallback must not select or create an inactive point.');
} catch (Illuminate\Validation\ValidationException $expected) {
    if ($db->table('stops_pickup')->count() !== 2) throw new RuntimeException('Unexpected fallback point created.');
}
echo "PASS: child and parent popup offer active points only, preserve status, reject inactive submissions and fallback.\n";
