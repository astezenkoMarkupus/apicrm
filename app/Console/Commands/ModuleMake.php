<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModuleMake extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name} {--all} {--migration} {--vue} {--view} {--controller} {--model} {--api}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Module name.
     *
     * @var string
     */
    protected $name;

    private Filesystem $files;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->files = $filesystem;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->name = $this->argument('name');

        if ($this->option('all')) {
            $this->input->setOption('migration', true);
            $this->input->setOption('vue', true);
            $this->input->setOption('view', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('model', true);
            $this->input->setOption('api', true);
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('vue')) {
            $this->createVueComponent();
        }

        if ($this->option('view')) {
            $this->createView();
        }

        if ($this->option('model')) {
            $this->createModel();
        }

        if ($this->option('controller')) {
            $this->createController();
        }

        if ($this->option('api')) {
            $this->createApiController();
        }
    }

    private function createMigration(): void
    {
        $table = Str::plural(Str::snake(class_basename($this->name)));

        try {
            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $this->name,
                '--path' => "App\\Modules\\".trim($this->name)."\\Migrations"
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

    }

    private function createVueComponent(): void
    {
        $path = $this->getVueComponentPath($this->name);

        $component = Str::studly(class_basename($this->name));

        if ($this->alreadyExists($path)) {
            $this->error('Vue Component already exists!');
        } else {
            $this->makeDirectory($path);

            $stub = $this->files->get(base_path('resources/stubs/vue.component.stub'));
            $stub = str_replace(['DummyClass'], [$component], $stub);
            $this->files->put($path, $stub);
            $this->info('Vue Component created successfully.');
        }
    }

    private function createView(): void
    {
        $paths = $this->getViewPath($this->argument('name'));

        foreach ($paths as $path) {
            $view = Str::studly(class_basename($this->argument('name')));

            if ($this->alreadyExists($path)) {
                $this->error('View already exists!');
            } else {
                $this->makeDirectory($path);

                $stub = $this->files->get(base_path('resources/stubs/view.stub'));

                $stub = str_replace(
                    [
                        '',
                    ],
                    [
                    ],
                    $stub
                );

                $this->files->put($path, $stub);
                $this->info('View created successfully.');
            }
        }
    }

    private function createController(): void
    {
        $controller = Str::studly(class_basename($this->name));
        $modelName = Str::singular($controller);
        $path = $this->getControllerPath($this->name);

        if ($this->alreadyExists($path)) {
            $this->error('Controller already exists!');
        } else {
            $this->makeDirectory($path);
            $stub = $this->files->get(base_path('resources/stubs/controller.model.api.stub'));
            $stub = str_replace(
                [
                    'DummyNamespace',
                    'DummyRootNamespace',
                    'DummyClass',
                    'DummyFullModelClass',
                    'DummyModelClass',
                    'DummyModelVariable'
                ],
                [
                    "App\\Modules\\".trim($this->name)."\\Controllers",
                    $this->laravel->getNamespace(),
                    $controller.'Controller',
                    "App\\Modules\\".trim($this->name)."\\Models\\$modelName",
                    $modelName,
                    lcfirst($modelName)
                ],
                $stub
            );

            $this->files->put($path, $stub);
            $this->info('Controller created successfully.');
//            $this->updateModularConfig();
        }

        $this->createRoutes($controller, $modelName);
    }

    private function createApiController(): void
    {
        $controller = Str::studly(class_basename($this->name));
        $modelName = Str::singular($controller);
        $path = $this->getApiControllerPath($this->name);

        if ($this->alreadyExists($path)) {
            $this->error('API Controller already exists!');
        } else {
            $this->makeDirectory($path);
            $stub = $this->files->get(base_path('resources/stubs/controller.model.api.stub'));
            $stub = str_replace(
                [
                    'DummyNamespace',
                    'DummyRootNamespace',
                    'DummyClass',
                    'DummyFullModelClass',
                    'DummyModelClass',
                    'DummyModelVariable'
                ],
                [
                    "App\\Modules\\".trim($this->name)."\\Controllers\\Api",
                    $this->laravel->getNamespace(),
                    $controller.'Controller',
                    "App\\Modules\\".trim($this->name)."\\Models\\$modelName",
                    $modelName,
                    lcfirst($modelName)
                ],
                $stub
            );

            $this->files->put($path, $stub);
            $this->info('API Controller created successfully.');
//            $this->updateModularConfig();
        }

        $this->createApiRoutes($controller, $modelName);
    }

    private function createModel(): void
    {
        $model = Str::singular(Str::studly(class_basename($this->name)));

        $this->call('make:model', ['name' => "App\\Modules\\".trim($this->name)."\\Models\\".$model]);
    }

    private function getControllerPath(string $name): string
    {
        $controller = Str::studly(class_basename($name));

        return $this->laravel['path'].'/Modules/'.str_replace('\\', '/',
                $name)."/Controllers/{$controller}Controller.php";
    }

    private function getApiControllerPath(string $name): string
    {
        $controller = Str::studly(class_basename($name));

        return $this->laravel['path'].'/Modules/'.str_replace('\\', '/',
                $name)."/Controllers/Api/{$controller}Controller.php";
    }

    private function makeDirectory(string $path): void
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    private function alreadyExists(string $path): bool
    {
        return $this->files->exists($path);
    }

    private function createRoutes(string $controller, string $modelName): void
    {
        $routePath = $this->getRoutesPath($this->name);

        if ($this->alreadyExists($routePath)) {
            $this->error('Routes already exists!');
        } else {

            $this->makeDirectory($routePath);

            $stub = $this->files->get(base_path('resources/stubs/routes.web.stub'));

            $stub = str_replace(
                [
                    'DummyClass',
                    'DummyRoutePrefix',
                    'DummyModelVariable',
                ],
                [
                    $controller.'Controller',
                    Str::plural(Str::snake(lcfirst($modelName), '-')),
                    lcfirst($modelName)
                ],
                $stub
            );

            $this->files->put($routePath, $stub);
            $this->info('Routes created successfully.');
        }
    }

    private function createApiRoutes(string $controller, string $modelName): void
    {
        $routePath = $this->getApiRoutesPath($this->name);

        if ($this->alreadyExists($routePath)) {
            $this->error('API Routes already exists!');
        } else {

            $this->makeDirectory($routePath);

            $stub = $this->files->get(base_path('resources/stubs/routes.api.stub'));

            $stub = str_replace(
                [
                    'DummyClass',
                    'DummyRoutePrefix',
                    'DummyModelVariable',
                ],
                [
                    'Api\\'.$controller.'Controller',
                    Str::plural(Str::snake(lcfirst($modelName), '-')),
                    lcfirst($modelName)
                ],
                $stub
            );

            $this->files->put($routePath, $stub);
            $this->info('API Routes created successfully.');
        }
    }

    private function getRoutesPath($name): string
    {
        return $this->laravel['path'].'/Modules/'.str_replace('\\', '/', $name)."/Routes/web.php";

    }

    private function getApiRoutesPath($name): string
    {
        return $this->laravel['path'].'/Modules/'.str_replace('\\', '/', $name)."/Routes/api.php";

    }

    protected function getVueComponentPath($name): string
    {
        return base_path('resources/js/components/'.str_replace('\\', '/', $name).".vue");
    }

    protected function getViewPath($name): object
    {
        $arrFiles = collect(['create', 'edit', 'index', 'show']);
        $paths = $arrFiles->map(function ($item) use ($name) {
            return base_path('resources/views/'.str_replace('\\', '/', $name).'/'.$item.".blade.php");
        });

        return $paths;
    }
}
