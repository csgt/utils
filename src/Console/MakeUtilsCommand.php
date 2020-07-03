<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeUtilsCommand extends Command
{
    protected $signature = 'make:csgtutils';

    protected $description = 'CSGT Utils';

    protected $views = [];

    protected $models = [
        'Menu' => 'Menu',
    ];

    public function handle()
    {
        $this->createDirectories();
        $this->exportModels();
        $this->exportLangs();

        $this->info('CSGT Views and controllers generated correctly.');
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

        if (!is_dir(app_path('Models/Menu'))) {
            mkdir(app_path('Models/Menu'), 0755, true);
        }
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
        return $this->laravel->getNamespace();
    }
}
