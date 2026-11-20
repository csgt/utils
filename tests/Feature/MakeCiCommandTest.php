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

    public function test_only_the_git_folder_ownership_is_repaired()
    {
        $this->artisan('make:csgtci', ['--branch' => 'main'])->assertExitCode(0);
        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));
        $start = strpos($workflow, '          ensure_repository_owner()');
        $end = strpos($workflow, '          configure_docker_compose()', $start);
        $functions = substr($workflow, $start, $end - $start);

        $this->assertStringContainsString('chown -R "$expected_owner" "$PRODUCTION_PATH/.git"', $functions);
        $this->assertStringNotContainsString('chown -R "$expected_owner" "$PRODUCTION_PATH"', $functions);

        // Execute the generated shell, with sudo simulated for an unprivileged fixture.
        $script = <<<'BASH'
set -euo pipefail
PRODUCTION_PATH=$(mktemp -d)
trap 'chmod -R u+rwx "$PRODUCTION_PATH"; rm -rf "$PRODUCTION_PATH"' EXIT
mkdir -p "$PRODUCTION_PATH/.git"
touch "$PRODUCTION_PATH/.git/FETCH_HEAD"
sudo() {
  echo "$*" >> "$PRODUCTION_PATH/sudo.log"
}
BASH;
        $script .= "\n" . $functions . <<<'BASH'

# Writable .git and FETCH_HEAD must not invoke sudo.
ensure_repository_owner
[ ! -e "$PRODUCTION_PATH/sudo.log" ]
# A read-only FETCH_HEAD triggers a repair limited to the .git folder.
chmod 400 "$PRODUCTION_PATH/.git/FETCH_HEAD"
ensure_repository_owner
grep -F -- "-n chown -R $(id -u):$(id -g) $PRODUCTION_PATH/.git" "$PRODUCTION_PATH/sudo.log"
BASH;
        $process = new \Symfony\Component\Process\Process(['bash', '-c', $script]);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
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
