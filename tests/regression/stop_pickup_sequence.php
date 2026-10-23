<?php

require __DIR__.'/datatable_related_sorting.php';

$actor->admin = true;
$controller = $app->make(App\Http\Controllers\StopPickupController::class);
$point = ['name'=>'Example', 'lat'=>23.02, 'lng'=>72.57];
foreach ([1=>1, 2=>2] as $routeId=>$pickupCount) {
    $route = App\Models\Route::findOrFail($routeId);
    $route->route_json = ['start_point'=>$point, 'pickup_points'=>array_fill(0, $pickupCount, $point), 'end_point'=>$point];
    $route->status = 1;
    $route->save();
}
$db->table('stops_pickup')->where('id', 1)->update(['route_id'=>1, 'sequence_order'=>99]);
$request = Illuminate\Http\Request::create('/', 'POST', ['iDisplayStart'=>0, 'iDisplayLength'=>100]);
$request->setUserResolver(fn ()=>$actor);
$app->instance('request', $request);
$result = $controller->stopPickupList($request)->getData(true);
$rows = $result['data'] ?? $result['aaData'];
$row = collect($rows)->firstWhere('id', 1);
if ((int) $row['sequence_order'] !== 3) throw new RuntimeException('Listing used stale sequence');
if ((int) $controller->edit(1)->getData()['stopPickup']->sequence_order !== 3) throw new RuntimeException('Edit used stale sequence');
foreach ([1=>3, 2=>4] as $routeId=>$expected) {
    $request = Illuminate\Http\Request::create('/', 'POST', [
        'route_id'=>$routeId, 'stop_name'=>'Example', 'latitude'=>23.02, 'longitude'=>72.57, 'sequence_order'=>999,
    ]);
    $request->setUserResolver(fn ()=>$actor);
    $app->instance('request', $request);
    $response = $controller->update($request, 1)->getData(true);
    if (!($response['success'] ?? false)) throw new RuntimeException(json_encode($response));
    if ((int) App\Models\StopPickup::findOrFail(1)->sequence_order !== $expected) throw new RuntimeException('Update trusted manual sequence or failed route change');
}
$blade = file_get_contents(__DIR__.'/../../resources/views/stop_pickup/edit.blade.php');
if (!preg_match('/name="sequence_order".*?readonly required autocomplete="off"/s', $blade)) throw new RuntimeException('Edit sequence is editable');
echo "PASS: route sequence consistent in listing/edit; tampered input ignored; changed route recalculated; edit readonly\n";
