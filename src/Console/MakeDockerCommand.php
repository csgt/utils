<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeDockerCommand extends Command
{
    protected $signature   = 'make:csgtdocker';
    protected $description = 'Create docker configurations';
    protected $directories = [
        'dockerfiles',
        'dockerfiles/mysql',
        'dockerfiles/app',
    ];
    protected $files = [
        'docker/docker-compose.yml.example.stub'       => 'docker-compose.yml.example',
        'docker/docker-compose.yml.stub'               => 'docker-compose.yml',
        'docker/dockerfiles/mysql/Dockerfile.stub'     => 'dockerfiles/mysql/Dockerfile',
        'docker/dockerfiles/app/Dockerfile.stub'       => 'dockerfiles/app/Dockerfile',
        'docker/dockerfiles/app/DockerfileDev.stub'    => 'dockerfiles/app/DockerfileDev',
        'docker/dockerfiles/app/php.ini.stub'          => 'dockerfiles/app/php.ini',
        'docker/dockerfiles/app/scheduler.sh.stub'     => 'dockerfiles/app/scheduler.sh',
        'docker/dockerfiles/app/start-container.stub'  => 'dockerfiles/app/start-container',
        'docker/dockerfiles/app/supervisord.conf.stub' => 'dockerfiles/app/supervisord.conf',
    ];

    public function handle()
    {
        if (is_dir(base_path('dockerfiles'))) {
            $this->error('Docker configurations are already published.');

            return;
        }
        $this->createDirectories();
        $this->exportFiles();
        $this->info('Docker configurations published correctly.');
    }

    protected function createDirectories()
    {
        foreach ($this->directories as $directory) {
            if (!is_dir(base_path($directory))) {
                mkdir(base_path($directory), 0755, true);
            }
        }
    }

    protected function exportFiles()
    {
        foreach ($this->files as $origin => $destination) {
            copy(
                __DIR__ . '/stubs/make/' . $origin,
                base_path($destination)
            );
        }
    }
}
