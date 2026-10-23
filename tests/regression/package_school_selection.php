<?php

// Run: php tests/regression/package_school_selection.php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection();
$db->getPdo()->sqliteCreateFunction('FIND_IN_SET', fn ($id, $ids) =>
    in_array((string) $id, explode(',', (string) $ids), true) ? 1 : 0, 2);
$db->statement('CREATE TABLE schools (id INTEGER PRIMARY KEY, school_name TEXT, deleted INTEGER, status INTEGER)');
$db->statement('CREATE TABLE package_details (id INTEGER PRIMARY KEY, school_id TEXT, user_id INTEGER, deleted INTEGER, status INTEGER)');
foreach ([1, 2, 3, 4] as $id) {
    $db->table('schools')->insert(['id' => $id, 'school_name' => 'School '.$id,
        'deleted' => $id === 4 ? 1 : 0, 'status' => $id === 3 ? 0 : 1]);
}
$controller = new class extends App\Http\Controllers\PackageDetailController {
    public int $schoolId = 1;
    protected function isPrivilegedActor(?Illuminate\Http\Request $request = null): bool { return false; }
    protected function isSchoolActor(?Illuminate\Http\Request $request = null): bool { return true; }
    protected function resolveSchoolIdFromContext(?Illuminate\Http\Request $request = null, ?int $ownerUserId = null): ?int {
        return $this->schoolId;
    }
};
$normalize = new ReflectionMethod(App\Http\Controllers\PackageDetailController::class, 'normalizeSelectedSchoolIds');
$request = fn ($ids) => Illuminate\Http\Request::create('/', 'POST', ['school_ids' => $ids]);
if ($normalize->invoke($controller, $request(['__all_schools__'])) !== [1, 2]) {
    throw new RuntimeException('All Schools must select only active, non-deleted schools');
}
if ($normalize->invoke($controller, $request(['2', '2'])) !== [2]) {
    throw new RuntimeException('Particular school selection changed');
}
$storage = new ReflectionMethod(App\Http\Controllers\PackageDetailController::class, 'schoolIdsToStorage');
$ids = $normalize->invoke($controller, $request(['__all_schools__']));
$db->table('package_details')->insert([
    ['id' => 1, 'school_id' => $storage->invoke($controller, $ids), 'user_id' => 99, 'deleted' => 0, 'status' => 0],
    ['id' => 2, 'school_id' => '2', 'user_id' => 99, 'deleted' => 0, 'status' => 0],
]);
$scope = new ReflectionMethod(App\Http\Controllers\PackageDetailController::class, 'packageAccessQuery');
foreach ([1 => [1], 2 => [1, 2], 3 => [], 4 => [], 5 => []] as $schoolId => $expected) {
    $controller->schoolId = $schoolId;
    $actual = $scope->invoke($controller, $request([]))->orderBy('id')->pluck('id')->all();
    if ($actual !== $expected) throw new RuntimeException('Incorrect visibility for school '.$schoolId);
}
// All Schools is a snapshot; later schools require an explicit assignment.
$db->table('schools')->insert(['id'=>5, 'school_name'=>'New school', 'deleted'=>0, 'status'=>1]);
$db->table('package_details')->insert([
    ['id'=>3, 'school_id'=>'all schools', 'user_id'=>99, 'deleted'=>0, 'status'=>0],
    ['id'=>4, 'school_id'=>null, 'user_id'=>99, 'deleted'=>0, 'status'=>0],
    ['id'=>5, 'school_id'=>'0', 'user_id'=>99, 'deleted'=>0, 'status'=>0],
]);
$controller->schoolId = 5;
if ($scope->invoke($controller, $request([]))->exists()) throw new RuntimeException('New school automatically inherited packages');
$controller->schoolId = 1;
if ($scope->invoke($controller, $request([]))->orderBy('id')->pluck('id')->all() !== [1]) throw new RuntimeException('Legacy global markers must not grant access');
$db->table('package_details')->where('id',1)->update(['school_id'=>'1,2,5']);
$controller->schoolId = 5;
if ($scope->invoke($controller, $request([]))->pluck('id')->all() !== [1]) throw new RuntimeException('Explicit assignment not visible');
echo "PASS: school snapshot, new school unassigned until explicitly assigned, legacy markers grant no access\n";
