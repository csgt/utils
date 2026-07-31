<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeDocsCommand extends Command
{
    protected $signature = 'make:csgtdocs
        {--php= : PHP version shown in the docs (auto-detected from composer.json when omitted)}
        {--node= : Node version shown in the docs (auto-detected from .nvmrc or package.json when omitted)}
        {--force : Overwrite the files if they already exist}';

    protected $description = 'Create the standard README.md and CLAUDE.md documentation';

    protected $files = [
        'docs/README.md.stub' => 'README.md',
        'docs/AGENTS.md.stub' => 'AGENTS.md',
        'docs/CLAUDE.md.stub' => 'CLAUDE.md',
    ];

    public function handle()
    {
        $replacements = [
            '{{PROJECT_NAME}}' => $this->resolveProjectName(),
            '{{REPO_SLUG}}'    => $this->resolveRepoSlug(),
            '{{PHP_VERSION}}'  => $this->option('php') ?: $this->resolvePhpVersion(),
            '{{NODE_VERSION}}' => $this->option('node') ?: $this->resolveNodeVersion(),
        ];

        $written = [];
        $skipped = [];

        foreach ($this->files as $stub => $destination) {
            $path = base_path($destination);

            if (file_exists($path) && !$this->option('force')) {
                $skipped[] = $destination;

                continue;
            }

            $contents = file_get_contents(__DIR__ . '/stubs/make/' . $stub);

            file_put_contents($path, str_replace(array_keys($replacements), array_values($replacements), $contents));

            $written[] = $destination;
        }

        foreach ($skipped as $file) {
            $this->warn($file . ' already exists, skipping. Use --force to overwrite.');
        }

        if (empty($written)) {
            return 1;
        }

        $this->info('Documentation published correctly: ' . implode(', ', $written) . '. Search for EDIT/TODO markers and fill in the project-specific sections.');

        return 0;
    }

    protected function resolveProjectName(): string
    {
        $composer = base_path('composer.json');

        if (is_file($composer)) {
            $data = json_decode((string) file_get_contents($composer), true);

            if (is_array($data) && isset($data['name']) && is_string($data['name']) && $data['name'] !== '') {
                return basename($data['name']);
            }
        }

        return basename(base_path());
    }

    protected function resolveRepoSlug(): string
    {
        $origin = trim((string) @shell_exec('git remote get-url origin 2>/dev/null'));

        if (preg_match('#github\.com[:/]([^/]+/[^/.]+)#', $origin, $matches)) {
            return $matches[1];
        }

        return 'OWNER/REPO';
    }

    protected function resolvePhpVersion(): string
    {
        $composer = base_path('composer.json');

        if (is_file($composer)) {
            $data = json_decode((string) file_get_contents($composer), true);

            if (is_array($data)) {
                $candidates = [
                    $data['config']['platform']['php'] ?? null,
                    $data['require']['php'] ?? null,
                ];

                foreach ($candidates as $candidate) {
                    if (is_string($candidate) && preg_match('/\d+\.\d+/', $candidate, $matches)) {
                        return $matches[0];
                    }
                }
            }
        }

        return '7.3';
    }

    protected function resolveNodeVersion(): string
    {
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

        return '14';
    }
}
