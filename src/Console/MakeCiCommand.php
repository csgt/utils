<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeCiCommand extends Command
{
    protected $signature = 'make:csgtci
        {--php=8.3 : PHP version used by the CI job}
        {--node=20 : Node version used by the CI job}
        {--branch= : Branch that triggers CI/CD (auto-detected when omitted)}
        {--force : Overwrite the workflow if it already exists}';

    protected $description = 'Create the CI/CD GitHub Actions workflow';

    protected $stub = 'ci/ci.yml.stub';

    protected $destination = '.github/workflows/ci.yml';

    public function handle()
    {
        if (!$this->validVersion('php') || !$this->validVersion('node')) {
            return self::FAILURE;
        }

        $destination = base_path($this->destination);

        if (file_exists($destination) && !$this->option('force')) {
            $this->error('CI/CD workflow already exists. Use --force to overwrite.');

            return self::FAILURE;
        }

        $directory = dirname($destination);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $branch = $this->resolveBranch();

        $contents = file_get_contents(__DIR__ . '/stubs/make/' . $this->stub);

        $contents = str_replace(
            ['{{PHP_VERSION}}', '{{NODE_VERSION}}', '{{BRANCH}}'],
            [$this->option('php'), $this->option('node'), $branch],
            $contents
        );

        file_put_contents($destination, $contents);

        $this->info('CI/CD workflow published correctly at ' . $this->destination . ' (branch: ' . $branch . ').');

        return self::SUCCESS;
    }

    protected function validVersion(string $option): bool
    {
        $value = (string) $this->option($option);

        if (!preg_match('/^\d+(\.\d+)*$/', $value)) {
            $this->error('Invalid --' . $option . ' version: ' . $value);

            return false;
        }

        return true;
    }

    protected function resolveBranch(): string
    {
        if ($branch = $this->option('branch')) {
            return $branch;
        }

        $default = trim((string) @shell_exec('git symbolic-ref --quiet --short refs/remotes/origin/HEAD 2>/dev/null'));

        if ($default !== '') {
            return preg_replace('#^origin/#', '', $default);
        }

        foreach (['main', 'master'] as $candidate) {
            $exists = trim((string) @shell_exec('git rev-parse --verify --quiet ' . escapeshellarg($candidate) . ' 2>/dev/null'));

            if ($exists !== '') {
                return $candidate;
            }
        }

        return 'main';
    }
}
