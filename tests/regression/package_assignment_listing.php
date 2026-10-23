<?php

require __DIR__.'/datatable_related_sorting.php';

$actor->admin = true;
$controller = $app->make(App\Http\Controllers\PackageDetailController::class);
$db->table('package_details')->where('id',1)->update(['school_id'=>'1,2']);
$db->table('package_details')->where('id',2)->update(['school_id'=>'all schools']);
$db->table('package_details')->where('id',3)->update(['school_id'=>null]);
$db->table('schools')->insert(['id'=>4,'school_name'=>'New school','deleted'=>0,'status'=>1,'user_id'=>99]);
$request = Illuminate\Http\Request::create('/', 'POST', ['iDisplayStart'=>0,'iDisplayLength'=>100]);
$request->setUserResolver(fn ()=>$actor);
$app->instance('request',$request);
$rows = collect($controller->packageDetailsList($request)->getData(true)['data'])->keyBy('id');
if ($rows[1]['school_name'] !== 'Zulu, Alpha') throw new RuntimeException('Snapshot assignments changed');
foreach ([2,3] as $id) {
    if ($rows[$id]['school_name'] !== '-') throw new RuntimeException('Legacy marker expands to current schools');
}
$html = file_get_contents(__DIR__.'/../../resources/views/package_details/edit.blade.php');
if (str_contains($html, '$isAllSchoolsPackage')) throw new RuntimeException('Edit silently selects new schools');
echo "PASS: assigned school names stay fixed after adding school; legacy markers do not expand in listing/edit\n";
