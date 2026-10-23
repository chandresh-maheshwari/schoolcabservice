<?php

// Isolated fixtures: no application database records are read or changed.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection();
$db->getPdo()->sqliteCreateCollation('utf8mb4_unicode_ci', fn ($left, $right) => strcasecmp($left, $right));
$db->getPdo()->sqliteCreateFunction('FIND_IN_SET', fn ($id, $list) =>
    ($position = array_search((string) $id, explode(',', (string) $list), true)) === false ? 0 : $position + 1, 2);
$models = ['School', 'VehicleType', 'Vehicle', 'Driver', 'Route', 'Parents', 'Child', 'PackageDetail',
    'Booking', 'StopPickup', 'DriverVehicleHistory', 'Emergency', 'Rating', 'User', 'ChildSubscription', 'SubscriptionPayment', 'SupportRequest'];
$extra = ['user_id', 'school_id', 'deleted', 'status', 'created_at', 'createdAt', 'updated_at',
    'is_assigned', 'current_lat', 'current_lng', 'parent_id', 'vehicle_id', 'driver_id', 'route_id',
    'vehicle_type_id', 'bus_id', 'route_json', 'pickup_point_id', 'stop_point_id', 'login_user_id'];
foreach ($models as $modelName) {
    $class = 'App\\Models\\'.$modelName;
    $model = new $class();
    $columns = array_unique(array_merge($extra, $model->getFillable(), array_keys($model->getAttributes())));
    $sql = [];
    foreach (array_diff($columns, ['id']) as $column) {
        $numeric = in_array($column, ['user_id','school_id','parent_id','vehicle_id','driver_id','route_id','vehicle_type_id','bus_id','deleted','status','is_assigned','amount','price','rating','validity_days'], true);
        $sql[] = '"'.$column.'" '.($numeric ? 'INTEGER' : 'TEXT');
    }
    $db->statement('CREATE TABLE "'.$model->getTable().'" (id INTEGER PRIMARY KEY, '.implode(', ', $sql).')');
}
$names = [1 => 'Zulu', 2 => 'Alpha', 3 => 'Mike'];
foreach ($names as $id => $name) {
    $base = ['id' => $id, 'user_id' => 99, 'school_id' => $id, 'deleted' => 0, 'status' => 0];
    $db->table('schools')->insert($base + ['school_name' => $name]);
    $db->table('vehicle_types')->insert($base + ['vehicle_type' => $name]);
    $db->table('vehicles')->insert($base + ['vehicle_type_id' => $id, 'vehicle_number' => $name, 'rc_number' => $name, 'insurance_number' => $name]);
    $db->table('drivers')->insert($base + ['driver_name' => $name, 'driver_phone' => $name, 'license_no' => $name, 'license_expiry_date' => '2030-01-0'.$id]);
    $db->table('routes')->insert($base + ['name' => $name, 'bus_id' => $id, 'driver_id' => $id, 'route_json' => '{}']);
    $db->table('parents')->insert($base + ['father_name' => $name, 'mother_name' => $name, 'contact_number' => $name]);
    $db->table('children')->insert($base + ['child_name' => $name, 'gender' => $name, 'parent_id' => $id, 'route_id' => $id]);
    $db->table('package_details')->insert($base + ['package_name' => $name, 'package_type' => $name, 'booking_type' => $name, 'price' => $id]);
    $db->table('bookings')->insert($base + ['package_type_id' => $id, 'booking_type_id' => $id, 'contact_number' => $name]);
    $db->table('emergency_incidents')->insert($base + ['driver_id' => $id, 'vehicle_id' => $id, 'reported_by' => $name, 'emergency_type' => $name, 'contact_number' => $name]);
    $db->table('ratings')->insert($base + ['driver_id' => $id, 'vehicle_id' => $id, 'rating' => $id, 'comments' => $name]);
    $db->table((new App\Models\StopPickup())->getTable())->insert($base + ['route_id' => $id, 'pickup_name' => $name, 'stop_name' => $name, 'sequence_order' => $id]);
    $db->table('driver_vehicle_histories')->insert($base + ['driver_id' => $id, 'vehicle_id' => $id, 'is_assigned' => 0]);
    $db->table('child_subscriptions')->insert($base + ['child_id' => $id, 'package_type' => $name]);
    $db->table('subscription_payments')->insert($base + ['child_subscription_id' => $id, 'amount' => $id, 'channel' => $name, 'receipt_no' => $name, 'reference_no' => $name, 'paid_at' => '2030-01-0'.$id]);
}
$actor = new class extends App\Models\User {
    public bool $admin = true;
    public function isAdmin(): bool { return $this->admin; }
    public function isSchool(): bool { return !$this->admin; }
    public function canAccessAdminRoute(?string $routeName): bool { return true; }
};
$actor->id = 99;
Illuminate\Support\Facades\Auth::setUser($actor);
$endpoints = [
    ['VehicleType', 'vehicleTypeList', ['vehicle_type']],
    ['Vehicle', 'vehicleList', ['vehicle_number', 'vehicle_type', 'rc_number', 'insurance_number']],
    ['Route', 'routeList', ['school_name', 'name', 'vehicle_number', 'driver_name']],
    ['Driver', 'driverList', ['school_name', 'driver_name', 'driver_phone', 'license_no', 'license_expiry_date']],
    ['Child', 'childList', ['school_name', 'child_name', 'father_name', 'name', 'gender']],
    ['Parent', 'parentList', ['school_name', 'father_name', 'mother_name', 'children_names', 'contact_number']],
    ['PackageDetail', 'packageDetailsList', ['school_name', 'package_name', 'package_type', 'booking_type', 'price']],
    ['Booking', 'bookingList', ['school_name', 'package_type', 'booking_type', 'contact_number']],
    ['School', 'schoolList', ['school_name']],
    ['Emergency', 'emergencyList', ['school_name', 'driver_name', 'vehicle_number', 'reported_by', 'emergency_type', 'contact_number']],
    ['Rating', 'ratingList', ['school_name', 'driver_name', 'vehicle_number', 'rating', 'comments']],
    ['StopPickup', 'stopPickupList', ['school_name', 'name', 'pickup_name', 'stop_name', 'sequence_order']],
    ['DriverVehicleHistory', 'driverHistoryList', ['school_name', 'driver_name', 'vehicle_number']],
    ['PaymentHistory', 'list', ['school_name', 'child_name', 'parent_name', 'package_type', 'amount', 'channel', 'receipt_no', 'reference_no', 'paid_at']],
];
$cases = 0;
foreach ([true, false] as $admin) {
    $actor->admin = $admin;
    foreach ($endpoints as [$name, $method, $fields]) {
        $controller = $app->make('App\\Http\\Controllers\\'.$name.'Controller');
        foreach (array_merge(['id'], $fields) as $field) {
            foreach (['asc', 'desc'] as $direction) {
                $request = Illuminate\Http\Request::create('/', 'POST', ['iDisplayStart' => 0, 'iDisplayLength' => 2,
                    'iSortCol_0' => 1, 'mDataProp_1' => $field, 'sSortDir_0' => $direction]);
                $request->setUserResolver(fn () => $actor);
                $app->instance('request', $request);
                $result = $controller->$method($request)->getData(true);
                $rows = $result['data'] ?? $result['aaData'] ?? null;
                if ($rows === null) throw new RuntimeException($name.' response: '.json_encode($result));
                $displayField = $name === 'StopPickup' && $field === 'name' ? 'route_name' : $field;
                $values = array_column($rows, $displayField);
                $sorted = $values;
                sort($sorted, in_array($field, ['id', 'price'], true) ? SORT_NUMERIC : SORT_STRING);
                if ($direction === 'desc') $sorted = array_reverse($sorted);
                if ($values !== $sorted) throw new RuntimeException("Incorrect sort: $name/$field/$direction");
                if ($admin && count($values) !== 2) throw new RuntimeException('Pagination lost records: '.$name);
                if (!$admin && !$values) throw new RuntimeException('School scope unexpectedly returned no records: '.$name);
                $cases++;
            }
        }
    }
}
echo "PASS: $cases admin/school ASC/DESC endpoint cases, related names, licences and pagination\n";
for ($id = 1; $id <= 15; $id++) $db->table('support_requests')->insert([
    'id'=>$id, 'user_id'=>99, 'parent_id'=>1, 'subject'=>'Support example', 'status'=>'open',
]);
$support = $app->make(App\Http\Controllers\MobileRequestController::class);
foreach ([true, false] as $admin) {
    $actor->admin = $admin;
    foreach (['asc'=>1, 'desc'=>15, 'invalid'=>15] as $direction=>$firstId) {
        $request = Illuminate\Http\Request::create('/supportRequests', 'GET', ['sort_direction'=>$direction, 'search'=>'Support']);
        $request->setUserResolver(fn ()=>$actor);
        $app->instance('request', $request);
        $paginator = $support->supportIndex($request)->getData()['requests'];
        if ((int) $paginator->first()->id !== $firstId || count($paginator) !== 12) throw new RuntimeException('Support order/pagination failed');
        if (!str_contains($paginator->url(2), 'sort_direction='.$direction)) throw new RuntimeException('Support ordering lost on page 2');
    }
}
echo "PASS: support card ASC/DESC ordering in admin/school panels; pagination retains search and order\n";
