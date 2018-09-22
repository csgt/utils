<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeUtilsCommand extends Command
{
    protected $signature = 'make:csgtutils';

    protected $description = 'CSGT Utils';

    protected $views = [
        'catalogs/roles/edit.stub' => 'catalogs/roles/edit.blade.php',
        'catalogs/users/edit.stub' => 'catalogs/users/edit.blade.php',
    ];

    protected $controllers = [
        'Catalogs/RolesController',
        'Catalogs/UsersController',
    ];

    protected $models = [
        'Menu' => 'Menu',
    ];

    protected $langs = [
        'es/usuario.stub' => 'es/usuario.php',
        'en/usuario.stub' => 'en/usuario.php',
    ];

    public function handle()
    {
        $this->createDirectories();
        $this->exportControllers();
        $this->exportModels();
        $this->exportViews();
        $this->exportLangs();

        $this->info('Vistas & Controladores para Utils generadas correctamente.');
    }

    protected function exportControllers()
    {
        foreach ($this->controllers as $controller) {
            file_put_contents(
                app_path('Http/Controllers/' . $controller . '.php'),
                $this->compileControllerStub($controller)
            );
        }
    }

    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            copy(
                __DIR__ . '/stubs/make/views/' . $key,
                base_path('resources/views/' . $value)
            );
        }
    }

    protected function exportLangs()
    {
        foreach ($this->langs as $key => $value) {
            copy(
                __DIR__ . '/stubs/make/lang/' . $key,
                base_path('resources/lang/' . $value)
            );
        }
    }

    protected function exportModels()
    {
        foreach ($this->models as $modelName => $folder) {
            file_put_contents(
                app_path('Models/' . ($folder != '' ? $folder . '/' : '') . $modelName . '.php'),
                $this->compileModelStub($modelName)
            );
        }
    }

    protected function createDirectories()
    {
        if (!is_dir(app_path('Http/Controllers/Catalogs'))) {
            mkdir(app_path('Http/Controllers/Catalogs'), 0755, true);
        }

        if (!is_dir(resource_path('views/catalogs/roles'))) {
            mkdir(resource_path('views/catalogs/roles'), 0755, true);
        }

        if (!is_dir(resource_path('views/catalogs/users'))) {
            mkdir(resource_path('views/catalogs/users'), 0755, true);
        }

        if (!is_dir(app_path('Models/Menu'))) {
            mkdir(app_path('Models/Menu'), 0755, true);
        }

        if (!is_dir(base_path('routes/core'))) {
            mkdir(base_path('routes/core'), 0755, true);
        }
    }

    protected function compileControllerStub($aPath, $aExtension = "stub")
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__ . '/stubs/make/controllers/' . $aPath . '.' . $aExtension)
        );
    }

    protected function compileModelStub($aModel, $aExtension = "stub")
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__ . '/stubs/make/models/' . $aModel . '.' . $aExtension)
        );
    }

    protected function getAppNamespace()
    {
        return 'App\\';
    }
}
