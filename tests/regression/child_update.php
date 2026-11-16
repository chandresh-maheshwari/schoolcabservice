<?php

// Reuse isolated transport fixtures; never writes to the application database.
require __DIR__.'/child_active_transport.php';
$db->statement('CREATE TABLE children (
    id INTEGER PRIMARY KEY, user_id INTEGER, school_id INTEGER, parent_id INTEGER,
    child_name TEXT, pickup_name TEXT, stop_name TEXT, route_id INTEGER, gender TEXT,
    date_of_birth TEXT, class TEXT, section TEXT, home_address TEXT,
    image TEXT, child_adhaar_card_image TEXT, child_adhaar_card_back_image TEXT,
    child_aadhaar_number TEXT, deleted INTEGER, updated_at TEXT, created_at TEXT
)');
$db->table('children')->insert([
    'id' => 129, 'user_id' => 1, 'school_id' => 10, 'child_name' => 'Before',
    'child_adhaar_card_back_image' => 'existing-back.jpg', 'deleted' => 0,
]);
$input = [
    'child_name' => 'Updated child', 'school_id' => '10', 'route_id' => '1',
    'pickup_name' => '1', 'stop_name' => '1', 'gender' => 'Male',
    'date_of_birth' => '2020-01-01', 'class' => 'Nursery',
];
$request = Illuminate\Http\Request::create('/admin/child/129', 'POST', $input);
$app->instance('request', $request);
$response = $child->update($request, 129)->getData(true);
if (!($response['success'] ?? false)) {
    throw new RuntimeException('Child update failed: '.json_encode($response));
}
if ($db->table('children')->where('id', 129)->value('child_name') !== 'Updated child') {
    throw new RuntimeException('Child changes were not saved.');
}
if ($db->table('children')->where('id', 129)->value('child_adhaar_card_back_image') !== 'existing-back.jpg') {
    throw new RuntimeException('Existing document was not preserved.');
}
$request->attributes->set('current_school', (object) ['id' => 99]);
$request->merge(['child_name' => 'Unauthorized change']);
$response = $child->update($request, 'other-school', 129)->getData(true);
if (($response['success'] ?? false)
    || $db->table('children')->where('id', 129)->value('child_name') !== 'Updated child') {
    throw new RuntimeException('Update must respect the school scope.');
}
$request->attributes->remove('current_school');
$db->table('children')->where('id', 129)->update(['deleted' => 1]);
$response = $child->update($request, 129)->getData(true);
if ($response['success'] ?? false) {
    throw new RuntimeException('Deleted child must not be updated.');
}
echo "PASS: child update loads the record, preserves documents, and rejects other-school/deleted records.\n";
