<?php

require __DIR__.'/cms_recreated_inactive.php';
$listMethods = ['AboutSection' => 'aboutSectionList', 'Service' => 'serviceList',
    'HowItWork' => 'howItWorkList', 'ClientSection' => 'clientSectionList',
    'BenefitSection' => 'benefitList', 'FaqSection' => 'faqList',
    'PricingPlanSection' => 'pricingPlanList', 'MsbAppSection' => 'msbAppSectionList',
    'SocialMedia' => 'socialMediaList'];
foreach ($modules as $controllerName => $modelName) {
    $class = 'App\\Models\\'.$modelName;
    $table = (new $class())->getTable();
    $db->table($table)->delete();
    foreach ([11, 12, 13] as $id) $db->table($table)->insert(['id' => $id, 'name' => 'Same name', 'deleted' => 0, 'status' => 0]);
    $controllerClass = 'App\\Http\\Controllers\\Frontend\\'.$controllerName.'Controller';
    $controller = new $controllerClass();
    foreach ([null => [13, 12], 'asc' => [11, 12], 'desc' => [13, 12]] as $direction => $expected) {
        $payload = ['iDisplayStart' => 0, 'iDisplayLength' => 2, 'iSortCol_0' => 0, 'mDataProp_0' => 'id'];
        if ($direction !== '') $payload['sSortDir_0'] = $direction;
        $response = $controller->{$listMethods[$controllerName]}(Illuminate\Http\Request::create('/', 'POST', $payload))->getData(true);
        $actual = array_map('intval', array_column($response['data'] ?? $response['aaData'], 'id'));
        if ($actual !== $expected) throw new RuntimeException($controllerName.' ordering failed: '.json_encode($actual));
    }
}
echo "PASS: 9 CMS list endpoints default ID DESC with pagination; explicit ASC/DESC preserved\n";
$visibleColumns = [
    'AboutSection' => ['title','name'], 'Service' => ['icon','name'],
    'HowItWork' => ['title','name'], 'ClientSection' => ['name'],
    'BenefitSection' => ['name','short_des'], 'FaqSection' => ['question','answer'],
    'PricingPlanSection' => ['title','amount','period','is_most_popular'],
    'MsbAppSection' => ['icon','name'], 'SocialMedia' => ['social_name','social_link','social_icon'],
];
$cases = 0;
foreach ($modules as $controllerName => $modelName) {
    $class = 'App\\Models\\'.$modelName;
    $table = (new $class())->getTable();
    foreach ([11 => 'Zulu', 12 => 'Alpha', 13 => 'Mike'] as $id => $value) {
        $updates = [];
        foreach ($visibleColumns[$controllerName] as $column) {
            $updates[$column] = in_array($column, ['amount','is_most_popular'], true) ? [11=>3,12=>1,13=>2][$id] : $value;
        }
        $db->table($table)->where('id', $id)->update($updates);
    }
    $controllerClass = 'App\\Http\\Controllers\\Frontend\\'.$controllerName.'Controller';
    $controller = new $controllerClass();
    foreach ($visibleColumns[$controllerName] as $column) foreach (['asc','desc'] as $direction) {
        $payload = ['iDisplayStart'=>0,'iDisplayLength'=>3,'iSortCol_0'=>1,'mDataProp_1'=>$column,'sSortDir_0'=>$direction];
        $response = $controller->{$listMethods[$controllerName]}(Illuminate\Http\Request::create('/', 'POST', $payload))->getData(true);
        $values = array_column($response['data'] ?? $response['aaData'], $column);
        $sorted = $values; sort($sorted, SORT_REGULAR);
        if ($direction === 'desc') $sorted = array_reverse($sorted);
        if ($values !== $sorted || count($values) !== 3) throw new RuntimeException("CMS sort failed: $controllerName/$column/$direction");
        $cases++;
    }
}
echo "PASS: $cases CMS visible-column ASC/DESC requests\n";
