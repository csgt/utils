<?php
namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Tests\TestCase;

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
        $this->artisan('make:csgtci', ['--php' => '8.3', '--node' => '20', '--branch' => 'main'])
            ->assertExitCode(0);

        $path = base_path('.github/workflows/ci.yml');

        $this->assertFileExists($path);

        $contents = file_get_contents($path);

        $this->assertStringNotContainsString('{{PHP_VERSION}}', $contents);
        $this->assertStringNotContainsString('{{NODE_VERSION}}', $contents);
        $this->assertStringNotContainsString('{{BRANCH}}', $contents);
        $this->assertStringContainsString('PHP_VERSION: "8.3"', $contents);
        $this->assertStringContainsString('NODE_VERSION: "20"', $contents);
        $this->assertStringContainsString('branches: ["main"]', $contents);
    }

    public function test_it_refuses_to_overwrite_without_force()
    {
        $this->artisan('make:csgtci', ['--php' => '8.3', '--node' => '20', '--branch' => 'main'])
            ->assertExitCode(0);

        $this->artisan('make:csgtci', ['--php' => '8.3', '--node' => '20', '--branch' => 'main'])
            ->assertExitCode(1);
    }

    public function test_force_overwrites_the_existing_workflow()
    {
        $this->artisan('make:csgtci', ['--php' => '8.3', '--node' => '20', '--branch' => 'main'])
            ->assertExitCode(0);

        $this->artisan('make:csgtci', ['--php' => '8.2', '--node' => '18', '--branch' => 'main', '--force' => true])
            ->assertExitCode(0);

        $this->assertStringContainsString(
            'PHP_VERSION: "8.2"',
            file_get_contents(base_path('.github/workflows/ci.yml'))
        );
    }

    public function test_an_invalid_version_fails()
    {
        $this->artisan('make:csgtci', ['--php' => 'banana', '--branch' => 'main'])
            ->assertExitCode(1);

        $this->assertFileDoesNotExist(base_path('.github/workflows/ci.yml'));
    }
}
