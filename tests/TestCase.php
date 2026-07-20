<?php
namespace Csgt\Utils\Tests;

use Csgt\Utils\UtilsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [UtilsServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.locale', 'es');
    }
}
