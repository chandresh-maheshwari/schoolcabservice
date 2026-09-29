<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection('sqlite');
$db->statement('CREATE TABLE drivers (id INTEGER PRIMARY KEY, adher_no TEXT, deleted INTEGER)');
$db->statement('CREATE TABLE children (id INTEGER PRIMARY KEY, child_aadhaar_number TEXT, deleted INTEGER)');
$db->statement('CREATE TABLE parents (id INTEGER PRIMARY KEY, father_aadhaar_number TEXT, mother_aadhaar_number TEXT, deleted INTEGER)');

$db->table('drivers')->insert(['id' => 1, 'adher_no' => '234567890123', 'deleted' => 0]);
$db->table('parents')->insert(['id' => 2, 'father_aadhaar_number' => '345678901234', 'mother_aadhaar_number' => '456789012345', 'deleted' => 0]);
$db->table('children')->insert(['id' => 3, 'child_aadhaar_number' => '567890123456', 'deleted' => 0]);
$db->table('drivers')->insert(['id' => 4, 'adher_no' => '678901234567', 'deleted' => 1]);
$db->table('children')->insert(['id' => 5, 'child_aadhaar_number' => '789012345678', 'deleted' => 1]);
$db->table('parents')->insert(['id' => 6, 'father_aadhaar_number' => '890123456789', 'mother_aadhaar_number' => '901234567890', 'deleted' => 1]);
$db->table('children')->insert(['id' => 7, 'child_aadhaar_number' => '912345678901', 'deleted' => null]);

$validator = new class extends App\Http\Controllers\Controller {
    public function check(array $entries): void
    {
        $this->validateGlobalAadhaarUniqueness($entries);
    }
};

$mustFail = function (array $entry) use ($validator): void {
    try {
        $validator->check([$entry]);
    } catch (Illuminate\Validation\ValidationException $exception) {
        return;
    }
    throw new RuntimeException('Duplicate Aadhaar number was accepted.');
};

$mustFail(['field' => 'father_aadhaar_number', 'number' => '2345 6789 0123']);
$mustFail(['field' => 'child_aadhaar_number', 'number' => '345678901234']);
$mustFail(['field' => 'adher_no', 'number' => '5678-9012-3456']);
$mustFail([
    'field' => 'mother_aadhaar_number',
    'number' => '345678901234',
    'ignore' => ['table' => 'parents', 'column' => 'mother_aadhaar_number', 'id' => 2],
]);

$validator->check([[
    'field' => 'adher_no',
    'number' => '234567890123',
    'ignore' => ['table' => 'drivers', 'column' => 'adher_no', 'id' => 1],
]]);
$validator->check([
    ['field' => 'child_aadhaar_number', 'number' => '678901234567'],
    ['field' => 'adher_no', 'number' => '789012345678'],
    ['field' => 'father_aadhaar_number', 'number' => '890123456789'],
    ['field' => 'mother_aadhaar_number', 'number' => '901234567890'],
]);
$mustFail(['field' => 'adher_no', 'number' => '912345678901']);

echo "PASS: Aadhaar uniqueness ignores deleted data across driver, child and parent modules\n";
