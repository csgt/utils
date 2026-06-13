<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class PublishCiCommand extends Command
{
    protected $signature = 'publish:ci
        {--php=8.3 : PHP version used by the CI job}
        {--node=20 : Node version used by the CI job}
        {--force : Overwrite the workflow if it already exists}';

    protected $description = 'Publish the CI/CD GitHub Actions workflow';

    protected $stub = 'ci/ci.yml.stub';

    protected $destination = '.github/workflows/ci.yml';

    public function handle()
    {
        $destination = base_path($this->destination);

        if (file_exists($destination) && !$this->option('force')) {
            $this->error('CI/CD workflow already exists. Use --force to overwrite.');

            return self::FAILURE;
        }

        $directory = dirname($destination);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $contents = file_get_contents(__DIR__ . '/stubs/make/' . $this->stub);

        $contents = str_replace(
            ['{{PHP_VERSION}}', '{{NODE_VERSION}}'],
            [$this->option('php'), $this->option('node')],
            $contents
        );

        file_put_contents($destination, $contents);

        $this->info('CI/CD workflow published correctly at ' . $this->destination . '.');

        return self::SUCCESS;
    }
}
