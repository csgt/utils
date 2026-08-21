<?php

namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Tests\TestCase;

class MakeUtilsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanScaffolding();
    }

    protected function tearDown(): void
    {
        $this->cleanScaffolding();

        parent::tearDown();
    }

    protected function cleanScaffolding()
    {
        @unlink(app_path('Models/Menu/Menu.php'));
        @rmdir(app_path('Models/Menu'));
        @rmdir(app_path('Http/Controllers/Catalogs'));
    }

    public function test_it_publishes_the_menu_model_and_directories()
    {
        $this->artisan('make:csgtutils')->assertExitCode(0);

        $this->assertDirectoryExists(app_path('Http/Controllers/Catalogs'));
        $this->assertFileExists(app_path('Models/Menu/Menu.php'));

        $model = file_get_contents(app_path('Models/Menu/Menu.php'));

        $this->assertStringNotContainsString('{{namespace}}', $model);
        $this->assertStringContainsString('namespace App\Models\Menu;', $model);
    }

    public function test_it_is_idempotent()
    {
        $this->artisan('make:csgtutils')->assertExitCode(0);
        $this->artisan('make:csgtutils')->assertExitCode(0);

        $this->assertFileExists(app_path('Models/Menu/Menu.php'));
    }
}
