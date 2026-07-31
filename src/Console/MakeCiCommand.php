<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeCiCommand extends Command
{
    protected $signature = 'make:csgtci
        {--php= : PHP version used by the CI job (auto-detected from composer.json when omitted)}
        {--node= : Node version used by the CI job (auto-detected from .nvmrc or package.json when omitted)}
        {--branch= : Branch that triggers CI/CD (auto-detected when omitted)}
        {--force : Overwrite the workflow if it already exists}';

    protected $description = 'Create the CI/CD GitHub Actions workflow';

    protected $stub = 'ci/ci.yml.stub';

    protected $destination = '.github/workflows/ci.yml';

    protected $defaultPhp = '7.3';

    protected $defaultNode = '12';

    public function handle()
    {
        $php = $this->resolvePhpVersion();
        $node = $this->resolveNodeVersion();

        if (!$this->validVersion('php', $php) || !$this->validVersion('node', $node)) {
            return 1;
        }

        $destination = base_path($this->destination);

        if (file_exists($destination) && !$this->option('force')) {
            $this->error('CI/CD workflow already exists. Use --force to overwrite.');

            return 1;
        }

        $directory = dirname($destination);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $branch = $this->resolveBranch();

        $contents = file_get_contents(__DIR__ . '/stubs/make/' . $this->stub);

        $contents = str_replace(
            ['{{PHP_VERSION}}', '{{NODE_VERSION}}', '{{BRANCH}}'],
            [$php, $node, $branch],
            $contents
        );

        file_put_contents($destination, $contents);

        $this->info('CI/CD workflow published correctly at ' . $this->destination . ' (branch: ' . $branch . ', php: ' . $php . ', node: ' . $node . ').');

        return 0;
    }

    protected function validVersion(string $option, string $value): bool
    {
        if (!preg_match('/^\d+(\.\d+)*$/', $value)) {
            $this->error('Invalid --' . $option . ' version: ' . $value);

            return false;
        }

        return true;
    }

    protected function resolvePhpVersion(): string
    {
        if ($php = $this->option('php')) {
            return $php;
        }

        $composer = base_path('composer.json');

        if (is_file($composer)) {
            $data = json_decode((string) file_get_contents($composer), true);

            if (is_array($data)) {
                $candidates = [
                    isset($data['config']['platform']['php']) ? $data['config']['platform']['php'] : null,
                    isset($data['require']['php']) ? $data['require']['php'] : null,
                ];

                foreach ($candidates as $candidate) {
                    if (is_string($candidate) && preg_match('/\d+\.\d+/', $candidate, $matches)) {
                        return $matches[0];
                    }
                }
            }
        }

        return $this->defaultPhp;
    }

    protected function resolveNodeVersion(): string
    {
        if ($node = $this->option('node')) {
            return $node;
        }

        $nvmrc = base_path('.nvmrc');

        if (is_file($nvmrc) && preg_match('/\d+(\.\d+)*/', (string) file_get_contents($nvmrc), $matches)) {
            return $matches[0];
        }

        $package = base_path('package.json');

        if (is_file($package)) {
            $data = json_decode((string) file_get_contents($package), true);

            if (is_array($data) && isset($data['engines']['node']) && is_string($data['engines']['node'])
                && preg_match('/\d+(\.\d+)*/', $data['engines']['node'], $matches)) {
                return $matches[0];
            }
        }

        return $this->defaultNode;
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
