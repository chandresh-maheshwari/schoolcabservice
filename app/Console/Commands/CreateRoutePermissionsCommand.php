<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\Permission;
use Illuminate\Support\Facades\File;

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
    protected $description = 'Create permissions for all named routes in web.php';

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
    public function handle()
    {
        $this->info('Starting to create permissions for named routes in web.php...');
        
        $routes = Route::getRoutes();
        $createdCount = 0;
        $existingCount = 0;
        
        $webRoutesContent = File::get(base_path('routes/web.php'));
        
        foreach ($routes as $route) {
            $routeName = $route->getName();
            
            if (empty($routeName)) {
                continue;
            }
            
            // Skip routes that are within auth middleware group
            if (in_array('auth', $route->middleware())) {
                $this->line("Skipping auth route: {$routeName}");
                continue;
            }
            
            if (strpos($webRoutesContent, "->name('{$routeName}')") !== false || 
                strpos($webRoutesContent, "->name(\"{$routeName}\")") !== false) {
                
                $permission = Permission::where('name', $routeName)->first();
                
                if (!$permission) {
                    Permission::create(['name' => $routeName]);
                    $createdCount++;
                    $this->line("Created permission: {$routeName}");
                } else {
                    $existingCount++;
                    $this->line("Permission already exists: {$routeName}");
                }
            }
        }
        
        $this->info("Completed! Created {$createdCount} new permissions. {$existingCount} permissions already existed.");
        
        return 0;
    }
} 