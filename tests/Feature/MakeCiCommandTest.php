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

    public function test_the_deploy_seeds_after_migrating()
    {
        $this->artisan('make:csgtci', ['--php' => '8.3', '--node' => '20', '--branch' => 'main'])
            ->assertExitCode(0);

        $contents = file_get_contents(base_path('.github/workflows/ci.yml'));

        $this->assertStringContainsString('artisan db:seed --force', $contents);
        $this->assertStringContainsString('artisan db:seed --class=GodSeeder --force', $contents);
        $this->assertStringContainsString('if [ -f database/seeders/GodSeeder.php ]; then', $contents);

        $this->assertLessThan(
            strpos($contents, 'artisan db:seed --force'),
            strpos($contents, 'artisan migrate --force'),
            'Seeders must run after migrations.'
        );
    }

    public function test_ci_provides_an_isolated_mysql_connection()
    {
        $this->artisan('make:csgtci', ['--branch' => 'main'])->assertExitCode(0);

        $contents = file_get_contents(base_path('.github/workflows/ci.yml'));
        [$ci, $deploy] = explode("\n  deploy:", $contents, 2);

        $this->assertStringContainsString('image: mysql:8.0', $ci);
        $this->assertStringContainsString('DB_HOST: 127.0.0.1', $ci);
        $this->assertStringContainsString('DB_PORT: "33060"', $ci);
        $this->assertStringContainsString('33060:3306', $ci);
        $this->assertStringContainsString('DB_DATABASE: ci_testing', $ci);
        $this->assertStringContainsString('MYSQL_DATABASE: ci_testing', $ci);
        $this->assertStringContainsString('DB_USERNAME: ci', $ci);
        $this->assertStringContainsString('MYSQL_USER: ci', $ci);
        $this->assertStringContainsString('pdo_mysql', $ci);
        $this->assertLessThan(strpos($ci, 'php artisan test'), strpos($ci, 'php artisan config:clear'));
        $this->assertStringNotContainsString('DB_DATABASE: ci_testing', $deploy);
    }

    public function test_the_deploy_uses_host_configured_ownership()
    {
        $this->artisan('make:csgtci', ['--branch' => 'main'])->assertExitCode(0);
        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));
        [, $deploy] = explode("\n  deploy:", $workflow, 2);

        $this->assertStringNotContainsString('chown ', $deploy);
        $this->assertStringNotContainsString('chgrp ', $deploy);
        $this->assertStringContainsString('git fetch origin "$DEPLOY_BRANCH"', $deploy);
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
