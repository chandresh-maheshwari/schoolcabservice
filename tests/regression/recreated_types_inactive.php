<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection();
foreach (['vehicle_types' => 'vehicle_type', 'emergency_types' => 'emergency_type'] as $table => $field) {
    $db->statement("CREATE TABLE $table (id INTEGER PRIMARY KEY AUTOINCREMENT, $field TEXT, user_id INTEGER, school_id INTEGER, status INTEGER, deleted INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT)");
    $db->statement("CREATE UNIQUE INDEX {$table}_{$field}_unique ON $table ($field)");
}
$migration = require dirname(__DIR__, 2).'/database/migrations/2026_10_22_000003_allow_new_records_for_deleted_type_names.php';
$migration->up();
trait TypeTestActor {
    public ?int $testSchool = null;
    protected function resolvePersistedUserId(Illuminate\Http\Request $request): ?int { return 99; }
    protected function resolveModuleOwnerUserId(?Illuminate\Http\Request $request = null, ?int $fallbackUserId = null, array $candidateUserIds = []): ?int { return 99; }
    protected function resolveModuleSchoolId(?Illuminate\Http\Request $request = null, ?int $fallbackSchoolId = null, array $candidateSchoolIds = [], ?int $ownerUserId = null): ?int { return $this->testSchool; }
}
$controllers = [
    [new class extends App\Http\Controllers\VehicleTypeController { use TypeTestActor; }, 'vehicle_types', 'vehicle_type'],
    [new class extends App\Http\Controllers\EmergencyTypeController { use TypeTestActor; }, 'emergency_types', 'emergency_type'],
];
foreach ($controllers as [$controller, $table, $field]) {
    foreach ([null, 7] as $school) {
        $controller->testSchool = $school;
        $name = $school ? 'School Example' : 'Admin Example';
        $request = fn () => Illuminate\Http\Request::create('/', 'POST', [$field => $name, 'status' => 1]);
        $controller->store($request());
        $record = $db->table($table)->where($field, $name)->first();
        if ((int) $record->status !== 0) throw new RuntimeException('Fresh type must be inactive');
        // Same state produced by activating and soft-deleting the listing row.
        $db->table($table)->where('id', $record->id)->update(['status' => 1, 'deleted' => 1]);
        $response = $controller->store($request())->getData(true);
        $recreated = $db->table($table)->where($field, $name)->where('deleted', 0)->first();
        if (!$response['success'] || (int) $recreated->status !== 0 || (int) $recreated->deleted !== 0
            || $recreated->school_id !== $school) throw new RuntimeException('Recreated type must be inactive in correct school');
        if ($recreated->id <= $record->id || (int) $db->table($table)->where('id', $record->id)->value('deleted') !== 1) {
            throw new RuntimeException('Recreate must insert a new ID and preserve the archived row');
        }
        if ($controller->store($request())->getStatusCode() !== 422) throw new RuntimeException('Existing non-deleted duplicate must remain blocked');
    }
}
echo "PASS: admin/school fresh and recreated vehicle/emergency types inactive; duplicate checks preserved\n";
