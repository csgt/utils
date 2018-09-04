<?php
namespace Csgt\Utils;

use Illuminate\Routing\Router;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class ComponentsServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot(Router $router)
    {
        AliasLoader::getInstance()->alias('CSGTMenu', 'Csgt\Utils\CSGTMenu');
        AliasLoader::getInstance()->alias('Utils', 'Csgt\Utils\Utils');

        $this->mergeConfigFrom(__DIR__ . '/config/csgtutils.php', 'csgtutils');
        $this->loadViewsFrom(__DIR__ . '/resources/views/', 'csgtutils');

        $router->aliasMiddleware('menu', '\Csgt\Utils\Http\Middleware\MenuMW');
        $router->aliasMiddleware('god', '\Csgt\Utils\Http\Middleware\GodMW');

        $this->publishes([
            __DIR__ . '/config/csgtutils.php' => config_path('csgtutils.php'),
        ], 'config');
    }

    public function register()
    {
        $this->commands([
            Console\MakeComponentsCommand::class,
        ]);

        $this->commands([
            Console\MakeDockerCommand::class,
        ]);

        $this->app->singleton('utils', function ($app) {
            return new Utils;
        });
    }

    public function provides()
    {
        return ['utils'];
    }
}
