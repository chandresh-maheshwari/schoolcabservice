<?php

require __DIR__.'/../../vendor/autoload.php';

$source = file_get_contents(__DIR__.'/../../resources/views/child/partials/add_child_modal.blade.php');
$start = strpos($source, '    $addChildTransportRouteMap =');
$end = strpos($source, '@endphp', $start);
$buildMap = substr($source, $start, $end - $start);
$addChildRoutePointOptions = [
    1 => [
        ['name' => 'Corporate road', 'type' => 'pickup'],
        ['name' => 'L.J. College Road', 'type' => 'pickup'],
    ],
    2 => [['name' => 'Road, District', 'type' => 'pickup']],
];
$addChildTransportOptions = collect([
    ['id' => 1, 'route_id' => 1, 'pickup_name' => 'Corporate road'],
    ['id' => 2, 'route_id' => 1, 'pickup_name' => 'L.J. College Road'],
    ['id' => 3, 'route_id' => 1, 'pickup_name' => 'L.J. College Road, Corporate road'],
    ['id' => 4, 'route_id' => 1, 'pickup_name' => ' CORPORATE  ROAD , L.J. College Road '],
    ['id' => 5, 'route_id' => 1, 'pickup_name' => 'Corporate road'],
    ['id' => 6, 'route_id' => 2, 'pickup_name' => 'Road, District'],
]);
eval($buildMap);
if (array_column($addChildTransportRouteMap[1], 'id') !== [1, 2]) {
    throw new RuntimeException('Popup must show individual pickup points once and omit legacy combined rows in either order.');
}
if (array_column($addChildTransportRouteMap[2], 'id') !== [6]) {
    throw new RuntimeException('A legitimate pickup name containing a comma must remain selectable.');
}
echo "PASS: child popup removes legacy combined pickups and duplicates; preserves individual pickup IDs and comma-containing names\n";
