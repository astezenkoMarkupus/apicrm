<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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

    /**
     * Execute the console command.
     */
    public function handle()
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

    private function createMigration()
    {
        $table = Str::plural(Str::snake(class_basename($this->name)));

        try {
            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $this->name,
                '--path' => "App\\Modules\\" . trim($this->name) . "\\Migrations"
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

    }

    private function createVueComponent()
    {

    }

    private function createView()
    {

    }

    private function createController()
    {
        $controller = Str::studly(class_basename($this->name));
        $modelName = Str::singular($controller);
        $path = $this->getControllerPath($this->name);

        if($this->alreadyExists($path)){
            $this->error('Controller already exists!');
        }else{
            $this->makeDirectory($path);
            $stub = $this->files->get(base_path('resources/stubs/controller.stub'));
            $stub = str_replace(
                [
                    'DummyNamespace',
                    'DummyRootNamespace',
                    'DummyClass',
                    'DummyFullModelClass',
                    'DummyModelClass',
                    'DummyModelVariable'
                ], [''], $stub);
        }
    }

    private function createApiController()
    {

    }

    private function createModel()
    {
        $model = Str::singular(Str::studly(class_basename($this->name)));

        $this->call('make:model', ['name' => "App\\Modules\\" . trim($this->name) . "\\Models\\" . $model]);
    }

    private function getControllerPath(string $name)
    {
        $controller = Str::studly(class_basename($name));

        return $this->laravel['path'] . '/Modules/' . str_replace('\\', '/', $name) . "/Controllers/{$controller}Controller.php";
    }

    private function getApiControllerPath(string $name)
    {
        $controller = Str::studly(class_basename($name));

        return $this->laravel['path'] . '/Modules/' . str_replace('\\', '/', $name) . "/Controllers/Api/{$controller}Controller.php";
    }

    private function makeDirectory(string $path)
    {

    }
}
