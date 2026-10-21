<?php

// Isolated fixtures: never writes to the application database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection();
$db->statement('CREATE TABLE schools (id INTEGER PRIMARY KEY, school_name TEXT, deleted INTEGER)');
$db->statement('CREATE TABLE drivers (id INTEGER PRIMARY KEY, driver_name TEXT, user_id INTEGER, vehicle_id INTEGER, updated_at TEXT)');
$db->statement('CREATE TABLE vehicles (id INTEGER PRIMARY KEY, vehicle_number TEXT, driver_id INTEGER, user_id INTEGER, updated_at TEXT)');
$db->statement('CREATE TABLE driver_vehicle_histories (id INTEGER PRIMARY KEY AUTOINCREMENT, driver_id INTEGER, vehicle_id INTEGER, school_id INTEGER, user_id INTEGER, is_assigned INTEGER, deleted INTEGER, created_at TEXT, updated_at TEXT)');
$db->table('schools')->insert(['id' => 1, 'school_name' => 'Test school', 'deleted' => 0]);
$db->table('drivers')->insert(['id' => 1, 'driver_name' => 'Test driver', 'user_id' => 99]);
$db->table('vehicles')->insert([
    ['id' => 1, 'vehicle_number' => 'TEST001'],
    ['id' => 2, 'vehicle_number' => 'TEST002'],
]);
$user = new class extends App\Models\User {
    public function isAdmin(): bool { return true; }
    public function isSchool(): bool { return false; }
};
Illuminate\Support\Facades\Auth::setUser($user);
$check = function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$route = new App\Http\Controllers\RouteController();
$sync = new ReflectionMethod($route, 'syncDriverVehicleLink');
$sync->invoke($route, 1, 1, 1);
$sync->invoke($route, 1, 1, 1);
$check($db->table('driver_vehicle_histories')->count() === 1, 'Repeated route save duplicated history');
$sync->invoke($route, 1, 2, 1);
$sync->invoke($route, 1, 1, 1);
$check($db->table('driver_vehicle_histories')->count() === 2, 'Reassignment duplicated an existing pair');
$check($db->table('driver_vehicle_histories')->where('is_assigned', 1)->count() === 1, 'Multiple active assignments');
$check($db->table('driver_vehicle_histories')->where('vehicle_id', 1)->value('user_id') === 99, 'Route history lost ownership');
// Legacy duplicates with different statuses/null deletion flags must collapse.
$db->table('driver_vehicle_histories')->insert([
    'driver_id' => 1, 'vehicle_id' => 1, 'school_id' => 1,
    'user_id' => 99, 'is_assigned' => 0, 'deleted' => null,
]);
$controller = new App\Http\Controllers\DriverVehicleHistoryController();
$list = function (array $options = []) use ($controller): array {
    return $controller->driverHistoryList(Illuminate\Http\Request::create('/', 'POST', $options + [
        'iDisplayStart' => 0, 'iDisplayLength' => 10, 'mDataProp_0' => 'id',
    ]))->getData(true);
};
foreach (['id', 'school_name', 'driver_name', 'vehicle_number'] as $sort) {
    $result = $list(['mDataProp_0' => $sort]);
    $check(count($result['data']) === 2 && $result['recordsTotal'] === 2 && $result['recordsFiltered'] === 2, 'Duplicate rows/counts for '.$sort);
}
$result = $list(['sSearch' => 'TEST001', 'iDisplayLength' => 1]);
$check(count($result['data']) === 1 && $result['recordsFiltered'] === 1, 'Search/pagination counted duplicates');
$controller->destroy($result['data'][0]['id']);
$check($list(['sSearch' => 'TEST001'])['recordsFiltered'] === 0, 'Duplicate resurfaced after single delete');
$sync->invoke($route, 1, 1, 1);
$check($list(['sSearch' => 'TEST001'])['recordsFiltered'] === 1, 'Reassignment after deletion failed');
$ids = array_column($list()['data'], 'id');
$controller->multiDelete(Illuminate\Http\Request::create('/', 'POST', ['ids' => $ids]));
$check($list()['recordsTotal'] === 0, 'Bulk deletion left duplicate history');
$own = $db->table('driver_vehicle_histories')->insertGetId([
    'driver_id' => 1, 'vehicle_id' => 1, 'school_id' => 1, 'user_id' => 200, 'deleted' => 0,
]);
$other = $db->table('driver_vehicle_histories')->insertGetId([
    'driver_id' => 1, 'vehicle_id' => 1, 'school_id' => 1, 'user_id' => 300, 'deleted' => 0,
]);
$scoped = new class extends App\Http\Controllers\DriverVehicleHistoryController {
    protected function shouldRestrictToActorData(?Illuminate\Http\Request $request = null): bool { return true; }
    protected function resolveActorUserId(?Illuminate\Http\Request $request = null): ?int { return 200; }
    protected function resolveSchoolIdFromContext(?Illuminate\Http\Request $request = null, ?int $ownerUserId = null): ?int { return null; }
};
$visible = $scoped->driverHistoryList(Illuminate\Http\Request::create('/', 'POST', ['iDisplayLength' => 10]))->getData(true);
$check(array_column($visible['data'], 'id') === [$own], 'Deduplication hid an authorized row behind another owner');
$scoped->multiDelete(Illuminate\Http\Request::create('/', 'POST', ['ids' => [$own, $other]]));
$check($db->table('driver_vehicle_histories')->where('id', $other)->value('deleted') === 0, 'Grouped deletion crossed ownership scope');
echo "PASS: repeated route saves, reassignment, legacy duplicates, sorting, counts, search, pagination and deletion\n";
