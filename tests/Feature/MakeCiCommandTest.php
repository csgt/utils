<?php
namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class MakeCiCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        @unlink(base_path('.github/workflows/ci.yml'));
    }

    protected function tearDown(): void
    {
        @unlink(base_path('.github/workflows/ci.yml'));

        parent::tearDown();
    }

    public function test_it_publishes_the_workflow_with_the_given_versions()
    {
        $code = Artisan::call('make:csgtci', ['--php' => '7.3', '--node' => '14', '--branch' => 'master']);

        $this->assertSame(0, $code);

        $path = base_path('.github/workflows/ci.yml');

        $this->assertTrue(file_exists($path));

        $contents = file_get_contents($path);

        $this->assertStringNotContainsString('{{PHP_VERSION}}', $contents);
        $this->assertStringNotContainsString('{{NODE_VERSION}}', $contents);
        $this->assertStringNotContainsString('{{BRANCH}}', $contents);
        $this->assertStringContainsString('PHP_VERSION: "7.3"', $contents);
        $this->assertStringContainsString('NODE_VERSION: "14"', $contents);
        $this->assertStringContainsString('branches: ["master"]', $contents);
    }

    public function test_it_refuses_to_overwrite_without_force()
    {
        $this->assertSame(0, Artisan::call('make:csgtci', ['--php' => '7.3', '--node' => '14', '--branch' => 'master']));
        $this->assertSame(1, Artisan::call('make:csgtci', ['--php' => '7.3', '--node' => '14', '--branch' => 'master']));
    }

    public function test_force_overwrites_the_existing_workflow()
    {
        $this->assertSame(0, Artisan::call('make:csgtci', ['--php' => '7.3', '--node' => '14', '--branch' => 'master']));
        $this->assertSame(0, Artisan::call('make:csgtci', ['--php' => '7.2', '--node' => '12', '--branch' => 'master', '--force' => true]));

        $this->assertStringContainsString(
            'PHP_VERSION: "7.2"',
            file_get_contents(base_path('.github/workflows/ci.yml'))
        );
    }

    public function test_an_invalid_version_fails()
    {
        $this->assertSame(1, Artisan::call('make:csgtci', ['--php' => 'banana', '--branch' => 'master']));
        $this->assertFalse(file_exists(base_path('.github/workflows/ci.yml')));
    }
}
