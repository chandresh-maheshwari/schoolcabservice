<?php

require __DIR__.'/vehicle_document_delete_persistence.php';
(require __DIR__.'/../../database/migrations/2026_10_23_000003_allow_license_number_without_document.php')->up();
$cases = [
    [App\Models\Driver::class, App\Http\Controllers\DriverController::class, [
        'licenseImage' => ['license_image', 'license_no'],
        'licenseBackImage' => ['license_back_image', 'license_expiry_date'],
        'adharCardImage' => ['adher_card_iamge', 'driver_name'],
        'adharCardBackImage' => ['adher_card_back_image', 'current_address'],
    ]],
    [App\Models\Parents::class, App\Http\Controllers\ParentController::class, [
        'parentAdhaarImage' => ['father_adhaar_card_image', 'father_name'],
        'parentAdhaarBackImage' => ['father_adhaar_card_back_image', 'address_1'],
        'motherAdhaarImage' => ['mother_adhaar_card_image', 'mother_name'],
        'motherAdhaarBackImage' => ['mother_adhaar_card_back_image', 'mother_aadhaar_number'],
    ]],
    [App\Models\Child::class, App\Http\Controllers\ChildController::class, [
        'childAdhaarImage' => ['child_adhaar_card_image', 'child_name'],
        'childAdhaarBackImage' => ['child_adhaar_card_back_image', 'home_address'],
    ]],
];
foreach ($cases as [$model, $controllerClass, $endpoints]) {
    $table = (new $model)->getTable();
    $controller = $app->make($controllerClass);
    $base = []; $sources = [];
    foreach ($endpoints as [$input, $field]) {
        $base[$input] = 'nonexistent-regression-'.$input.'.jpg';
        $base[$field] = str_contains($field, 'expiry') ? '2035-01-01' : 'TEST-'.$field;
        $sources[$field] = $input;
    }
    foreach ($endpoints as $method => [$input, $field]) {
        foreach ([false, true] as $manual) {
            $db->table($table)->where('id', 1)->update($base + ['document_field_sources' => json_encode($sources)]);
            $live = $sources;
            if ($manual) unset($live[$field]);
            $app->instance('request', Illuminate\Http\Request::create('/delete', 'DELETE', [
                'document_field_source_model' => $table, 'document_field_sources' => json_encode($live),
            ]));
            $response = $controller->$method(1)->getData(true);
            $record = $model::findOrFail(1);
            if (! $response['success'] || $record->$input !== null) throw new RuntimeException($method.' image not removed');
            foreach ($sources as $otherField => $owner) {
                $expected = $otherField === $field && ! $manual ? null : $base[$otherField];
                if ($record->getRawOriginal($otherField) !== $expected) throw new RuntimeException($method.' wrong persisted field: '.$otherField);
            }
            if ($response['cleared_fields'] !== ($manual ? [] : [$field])) throw new RuntimeException($method.' incorrect response');
            if (isset($record->document_field_sources[$field])) throw new RuntimeException($method.' stale source');
        }
    }
}
echo "PASS: all 13 document delete endpoints persist source-specific clearing and preserve opposite-side/manual values\n";
