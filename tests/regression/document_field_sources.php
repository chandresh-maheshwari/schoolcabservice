<?php

require __DIR__.'/datatable_related_sorting.php';

$migration = require __DIR__.'/../../database/migrations/2026_10_23_000001_add_document_field_sources.php';
$migration->up();
$cases = [
    [App\Models\Driver::class, 'license_no', 'license_image'],
    [App\Models\Parents::class, 'father_name', 'father_adhaar_card_image'],
    [App\Models\Child::class, 'child_name', 'modal_child_adhaar_card_image'],
    [App\Models\Vehicle::class, 'insurance_number', 'insurance_image'],
];
foreach ($cases as [$class, $field, $input]) {
    $model = $class::findOrFail(1);
    $canonical = str_starts_with($input, 'modal_') ? substr($input, 6) : $input;
    $request = Illuminate\Http\Request::create('/', 'POST', [
        'document_field_source_model'=>$model->getTable(),
        'document_field_sources'=>json_encode([$field=>$input, 'password'=>'license_image', 'status'=>'invalid_input']),
    ]);
    $app->instance('request', $request);
    $model->save();
    $model->refresh();
    if ($model->document_field_sources !== [$field=>$canonical]) throw new RuntimeException('Source round trip failed: '.$class);
    $app->instance('request', Illuminate\Http\Request::create('/', 'POST'));
    $model->save();
    if ($model->fresh()->document_field_sources !== [$field=>$canonical]) throw new RuntimeException('Ordinary update lost sources');
    $app->instance('request', Illuminate\Http\Request::create('/', 'POST', [
        'document_field_source_model'=>'unrelated_table', 'document_field_sources'=>'{}',
    ]));
    $model->save();
    if ($model->fresh()->document_field_sources !== [$field=>$canonical]) throw new RuntimeException('Unrelated form changed sources');
    $app->instance('request', Illuminate\Http\Request::create('/', 'POST', [
        'document_field_source_model'=>$model->getTable(), 'document_field_sources'=>'{}',
    ]));
    $model->save();
    if ($model->fresh()->document_field_sources !== []) throw new RuntimeException('Explicit cleared sources not persisted');
}
$migration->down();
echo "PASS: source metadata persists across edit/reload in all 4 models; modal IDs normalized; unrelated updates preserve it; migration up/down\n";
