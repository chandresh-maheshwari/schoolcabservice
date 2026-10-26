<?php

require __DIR__.'/datatable_related_sorting.php';
$actor->admin = true;
(require __DIR__.'/../../database/migrations/2026_10_23_000001_add_document_field_sources.php')->up();
(require __DIR__.'/../../database/migrations/2026_10_23_000002_allow_vehicle_number_without_rc.php')->up();
$controller = $app->make(App\Http\Controllers\VehicleController::class);
$base = ['rc_image'=>'nonexistent-front.jpg', 'rc_back_image'=>'nonexistent-back.jpg', 'insurance_image'=>'nonexistent-insurance.jpg',
    'vehicle_number'=>'TEST123', 'rc_number'=>'TEST123', 'rc_expiry_date'=>'2035-01-01',
    'insurance_number'=>'POLICY123', 'insurance_expiry_date'=>'2036-01-01'];
$split = ['vehicle_number'=>'rc_back_image', 'rc_number'=>'rc_back_image', 'rc_expiry_date'=>'rc_image',
    'insurance_number'=>'insurance_image', 'insurance_expiry_date'=>'insurance_image'];
$reset = function ($sources) use ($db, $base) {
    $db->table('vehicles')->where('id',1)->update($base+['document_field_sources'=>$sources === null ? null : json_encode($sources)]);
};
$request = function ($sources = null) use ($app) {
    $payload = $sources === null ? [] : ['document_field_source_model'=>'vehicles', 'document_field_sources'=>json_encode($sources)];
    $app->instance('request', Illuminate\Http\Request::create('/delete','DELETE',$payload));
};
$reset($split); $request($split);
$response = $controller->rcImage(1)->getData(true);
$v = App\Models\Vehicle::findOrFail(1);
if ($response['cleared_fields'] !== ['rc_expiry_date'] || $v->rc_image !== null || $v->rc_expiry_date !== null
    || $v->rc_number !== 'TEST123' || $v->insurance_number !== 'POLICY123') throw new RuntimeException('Front delete changed another document data');
$request($v->document_field_sources);
$controller->rcBackImage(1);
$v->refresh();
if ($v->rc_number !== null || $v->vehicle_number !== null || $v->insurance_number !== 'POLICY123') throw new RuntimeException('Back delete did not persist owned fields');
$reset(null); $request(); $controller->rcImage(1); $v->refresh();
if ($v->rc_number !== null || $v->rc_expiry_date !== null || $v->vehicle_number !== null) throw new RuntimeException('Legacy RC front data returned after reload');
$reset($split); $request(['rc_expiry_date'=>'rc_image']); $controller->rcImage(1); $v->refresh();
if ($v->rc_number !== 'TEST123' || $v->vehicle_number !== 'TEST123') throw new RuntimeException('Manual field ownership not respected');
$reset($split); $request($split); $controller->insuranceImage(1); $v->refresh();
if ($v->insurance_number !== null || $v->insurance_expiry_date !== null || $v->rc_number !== 'TEST123') throw new RuntimeException('Insurance delete affected RC');
$request(); $response = $controller->insuranceImage(1);
if ($response->getStatusCode() !== 404 || App\Models\Vehicle::findOrFail(1)->rc_number !== 'TEST123') throw new RuntimeException('Failed delete altered fields');
echo "PASS: actual delete endpoints persist only owned RC/insurance fields; opposite-side/manual data preserved; legacy records and failed deletes covered\n";
