<?php
namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Tests\TestCase;
use Csgt\Utils\Utils;
use Illuminate\Support\Facades\Artisan;

class ServiceProviderTest extends TestCase
{
    public function test_the_utils_singleton_resolves()
    {
        $this->assertInstanceOf(Utils::class, $this->app->make('utils'));
    }

    public function test_the_scaffolding_commands_are_registered()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('make:csgtci', $commands);
        $this->assertArrayHasKey('make:csgtdocs', $commands);
        $this->assertArrayHasKey('make:csgtdocker', $commands);
        $this->assertArrayHasKey('make:csgtutils', $commands);
    }
}
