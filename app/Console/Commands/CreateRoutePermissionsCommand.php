<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\Permission;

class CreateRoutePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:create-route-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync UI permissions from protected (web) routes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    // public function handle()
    // {
    //     $this->info('Starting to create permissions for named routes in web.php...');

    //     $routes = Route::getRoutes();
    //     $createdCount = 0;
    //     $existingCount = 0;

    //     $webRoutesContent = File::get(base_path('routes/web.php'));

    //     foreach ($routes as $route) {
    //         $routeName = $route->getName();

    //         if (empty($routeName)) {
    //             continue;
    //         }

    //         // Skip routes that are within auth middleware group
    //         if (in_array('auth', $route->middleware())) {
    //             $this->line("Skipping auth route: {$routeName}");
    //             continue;
    //         }

    //         if (strpos($webRoutesContent, "->name('{$routeName}')") !== false ||
    //             strpos($webRoutesContent, "->name(\"{$routeName}\")") !== false) {

    //             $permission = Permission::where('name', $routeName)->first();

    //             if (!$permission) {
    //                 Permission::create(['name' => $routeName]);
    //                 $createdCount++;
    //                 $this->line("Created permission: {$routeName}");
    //             } else {
    //                 $existingCount++;
    //                 $this->line("Permission already exists: {$routeName}");
    //             }
    //         }
    //     }

    //     $this->info("Completed! Created {$createdCount} new permissions. {$existingCount} permissions already existed.");

    //     return 0;
    // }


    public function handle()
    {
        $this->info('Syncing permissions from protected (web) routes...');

        $routes = Route::getRoutes();

        $created = 0;
        $restored = 0;
        $existing = 0;
        $softDeleted = 0;

        $assignableRouteNames = [];
        $apiRouteNames = [];

        foreach ($routes as $route) {
            $routeName = $route->getName();
            if (! $routeName) {
                continue;
            }

            $middlewares = $route->gatherMiddleware();
            $isApiRoute = in_array('api', $middlewares, true) || str_starts_with($routeName, 'api.');
            if ($isApiRoute) {
                $apiRouteNames[] = $routeName;
                continue;
            }

            if (! in_array('permission', $middlewares, true)) {
                continue;
            }

            // School panel routes are named like `school.vehicle.index` but permissions are stored
            // against the base route names (e.g. `vehicle.index`).
            if (str_starts_with($routeName, 'school.')) {
                continue;
            }

            $assignableRouteNames[] = $routeName;
        }

        $assignableRouteNames = array_values(array_unique($assignableRouteNames));
        sort($assignableRouteNames);

        foreach ($assignableRouteNames as $routeName) {
            $permission = Permission::where('name', $routeName)->orderBy('id')->first();

            if (! $permission) {
                Permission::create(['name' => $routeName]);
                $created++;
                $this->line("Created permission: {$routeName}");
                continue;
            }

            if ((int) $permission->deleted === 1) {
                $permission->deleted = 0;
                $permission->save();
                $restored++;
            } else {
                $existing++;
            }
        }

        // Soft-delete API and system permissions so they don't appear in the role UI.
        $apiRouteNames = array_values(array_unique($apiRouteNames));
        if (! empty($apiRouteNames)) {
            $softDeleted += Permission::whereIn('name', $apiRouteNames)->update(['deleted' => 1]);
        }

        $systemPrefixes = ['sanctum.', 'ignition.', 'telescope.', '_debugbar.'];
        foreach ($systemPrefixes as $prefix) {
            $softDeleted += Permission::where('name', 'like', $prefix . '%')->update(['deleted' => 1]);
        }

        // Soft-delete nested school permissions like `school.vehicleType.index` (keep `school.index`, `school.store`, etc).
        $softDeleted += Permission::where('name', 'like', 'school.%.%')->update(['deleted' => 1]);

        $this->info("=================================");
        $this->info("New Permissions Created: {$created}");
        $this->info("Permissions Restored: {$restored}");
        $this->info("Already Existing: {$existing}");
        $this->info("Soft Deleted (API/System): {$softDeleted}");
        $this->info("=================================");

        return Command::SUCCESS;
    }
}
