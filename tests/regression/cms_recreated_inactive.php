<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]]);
$db = Illuminate\Support\Facades\DB::connection();
$modules = [
    'AboutSection' => 'AboutSection', 'Service' => 'Service', 'HowItWork' => 'HowItWork',
    'ClientSection' => 'ClientSection', 'BenefitSection' => 'BenefitSection',
    'FaqSection' => 'FaqSection', 'PricingPlanSection' => 'PricingPlanSection',
    'MsbAppSection' => 'MsbAppSection', 'SocialMedia' => 'SocialMediaSection',
];
foreach ($modules as $controllerName => $modelName) {
    $modelClass = 'App\\Models\\'.$modelName;
    $model = new $modelClass();
    $table = $model->getTable();
    $columns = array_unique(array_merge($model->getFillable(), array_keys($model->getAttributes()), ['name', 'status', 'deleted', 'created_at', 'updated_at']));
    $definitions = array_map(fn ($column) => $column === 'status' || $column === 'deleted'
        ? '"'.$column.'" INTEGER DEFAULT 0' : '"'.$column.'" TEXT', array_diff($columns, ['id']));
    $db->statement('CREATE TABLE "'.$table.'" (id INTEGER PRIMARY KEY AUTOINCREMENT, '.implode(', ', $definitions).')');
    $controllerClass = 'App\\Http\\Controllers\\Frontend\\'.$controllerName.'Controller';
    $controller = new $controllerClass();
    $payload = ['name' => 'Same Name', 'title' => 'Example', 'description' => 'Example description',
        'button_name' => 'Open', 'button_link' => 'https://example.com', 'icon' => 'icon',
        'short_des' => 'Description', 'question' => 'Question', 'answer' => 'Answer',
        'plan_icon' => 'icon', 'currency_icon' => 'icon', 'amount' => 10, 'period' => 'Monthly',
        'social_name' => 'Same Name', 'social_link' => 'https://example.com', 'social_icon' => 'icon', 'status' => 1];
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $response = $controller->store(Illuminate\Http\Request::create('/', 'POST', $payload));
        $row = $db->table($table)->orderByDesc('id')->first();
        if ($response->getStatusCode() !== 200 || !$row || (int) $row->status !== 0) {
            throw new RuntimeException($controllerName.' did not create inactive: '.$response->getContent());
        }
        if ($attempt === 0) $db->table($table)->where('id', $row->id)->update(['status' => 1, 'deleted' => 1]);
    }
}
echo "PASS: 9 CMS modules create and recreate inactive after active soft deletion, even with status=1 in request\n";
