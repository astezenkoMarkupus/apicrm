<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModularProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $modules = config('modular.modules');
        $path = config('modular.path');

        if ($modules) {
            Route::group([
                'prefix' => '',
            ], function () use ($modules, $path) {
                foreach ($modules as $mod => $submodules) {
                    foreach ($submodules as $key => $sub) {
                        $relativePath = "/$mod/$sub";

                        Route::middleware('web')
                            ->group(function () use ($mod, $sub, $relativePath, $path) {
                                $this->getWebRoutes($mod, $sub, $relativePath, $path);
                            });

                        Route::prefix('api')
                            ->middleware('api')
                            ->group(function () use ($mod, $sub, $relativePath, $path) {
                                $this->getApiWebRoutes($mod, $sub, $relativePath, $path);
                            });
                    }
                }
            });
        }
    }

    private function getWebRoutes(int|string $mod, mixed $sub, string $relativePath, mixed $path)
    {
        $routesPath = $path.$relativePath.'/Routes/web.php';

        if (file_exists($routesPath)) {
            if ($mod !== config('modular.groupWithoutPrefix')) {
                Route::group([
                    'prefix' => strtolower($mod),
                    'middleware' => $this->getMiddleware($mod),
                ], function () use ($mod, $sub, $routesPath) {
                    Route::namespace("App\Modules\\$mod\\$sub\Controllers")->group($routesPath);
                });
            } else {
                Route::namespace("App\Modules\\$mod\\$sub\Controllers")
                    ->middleware($this->getMiddleware($mod))
                    ->group($routesPath);
            }
        }
    }

    private function getApiWebRoutes(int|string $mod, mixed $sub, string $relativePath, mixed $path): void
    {
        $routesPath = $path.$relativePath.'/Routes/api.php';

        if (file_exists($routesPath)) {
            Route::group([
                'prefix' => strtolower($mod),
                'middleware' => $this->getMiddleware($mod, 'api'),
            ], function () use ($mod, $sub, $routesPath) {
                Route::namespace("App\Modules\\$mod\\$sub\Controllers")->group($routesPath);
            });
        }
    }

    private function getMiddleware(string $mod, string $type = 'web')
    {
        $middleware = [];
        $config = config('modular.groupMiddleware');

        if(isset($config[$mod])){
            if(array_key_exists($type, $config[$mod])){
                $middleware = array_merge($middleware, $config[$mod][$type]);
            }
        }

        return $middleware;
    }
}
