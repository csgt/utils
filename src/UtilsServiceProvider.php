<?php
namespace Csgt\Utils;

use Illuminate\Routing\Router;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class UtilsServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot(Router $router)
    {
        AliasLoader::getInstance()->alias('CSGTMenu', 'Csgt\Utils\CSGTMenu');
        AliasLoader::getInstance()->alias('Utils', 'Csgt\Utils\Utils');

        $this->loadViewsFrom(__DIR__ . '/resources/views/', 'csgtutils');

        $router->aliasMiddleware('menu', '\Csgt\Utils\Http\Middleware\MenuMW');
        $router->aliasMiddleware('god', '\Csgt\Utils\Http\Middleware\GodMW');

    }

    public function register()
    {
        $this->commands([
            Console\MakeUtilsCommand::class,
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
