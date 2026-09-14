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

    public function test_git_permissions_are_checked_and_repaired_without_touching_application_files()
    {
        $this->artisan('make:csgtci', ['--branch' => 'main'])->assertExitCode(0);
        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));
        $start = strpos($workflow, '          repository_permissions_ok()');
        $end = strpos($workflow, '          configure_docker_compose()', $start);
        $functions = substr($workflow, $start, $end - $start);

        // Execute the generated shell, with sudo simulated for an unprivileged fixture.
        $script = <<<'BASH'
set -euo pipefail
[ "$(id -u)" != 0 ] || exit 77
PRODUCTION_PATH=$(mktemp -d)
trap 'chmod -R u+rwx "$PRODUCTION_PATH"; rm -rf "$PRODUCTION_PATH"' EXIT
mkdir -p "$PRODUCTION_PATH/.git/objects/ab" "$PRODUCTION_PATH/.git/refs/heads"
touch "$PRODUCTION_PATH/.git/FETCH_HEAD" "$PRODUCTION_PATH/.git/objects/ab/object" "$PRODUCTION_PATH/app.php"
chmod 444 "$PRODUCTION_PATH/.git/objects/ab/object" "$PRODUCTION_PATH/app.php"
log() { :; }
sudo() {
  echo "$*" >> "$PRODUCTION_PATH/sudo.log"
  [ "$1" = -n ]; shift
  [ "${deny:-0}" = 0 ] || return 1
  [ "${noop:-0}" = 0 ] || return 0
  if [ "$1" = chown ]; then
    [ "$3" = "$(id -u):$(id -g)" ]
    [ "$4" = "$PRODUCTION_PATH/.git" ]
    return
  fi
  "$@"
}
BASH;
        $script .= "\n" . $functions . <<<'BASH'

# Healthy metadata and read-only objects must not invoke sudo.
ensure_repository_owner
[ ! -e "$PRODUCTION_PATH/sudo.log" ]
# Reproduce the failure even though .git and FETCH_HEAD are writable.
chmod 500 "$PRODUCTION_PATH/.git/objects/ab"
[ -w "$PRODUCTION_PATH/.git" ] && [ -w "$PRODUCTION_PATH/.git/FETCH_HEAD" ]
if repository_permissions_ok; then exit 10; fi
ensure_repository_owner
[ -w "$PRODUCTION_PATH/.git/objects/ab" ]
[ ! -w "$PRODUCTION_PATH/.git/objects/ab/object" ]
[ ! -w "$PRODUCTION_PATH/app.php" ]
# Mutable metadata also needs write access.
chmod 400 "$PRODUCTION_PATH/.git/FETCH_HEAD"
ensure_repository_owner
[ -w "$PRODUCTION_PATH/.git/FETCH_HEAD" ]
# Denied sudo and an ineffective repair must both stop deployment.
chmod 500 "$PRODUCTION_PATH/.git/objects/ab"
deny=1
if ensure_repository_owner > "$PRODUCTION_PATH/error.log"; then exit 11; fi
/usr/bin/grep -F "Cannot repair Git permissions for $(id -un) at $PRODUCTION_PATH/.git" "$PRODUCTION_PATH/error.log"
deny=0
noop=1
if ensure_repository_owner; then exit 12; fi
BASH;
        $process = new \Symfony\Component\Process\Process(['bash', '-c', $script]);
        $process->run();

        if ($process->getExitCode() === 77) {
            $this->markTestSkipped('Permission checks require a non-root user.');
        }

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
