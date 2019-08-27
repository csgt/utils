<?php
namespace Csgt\Utils\Console;

use Illuminate\Console\Command;

class MakeDockerCommand extends Command
{
    protected $signature   = 'make:csgtdocker';
    protected $description = 'Crear configuraciones Docker';
    protected $directories = [
        'dockerfiles',
    ];
    protected $files = [
        'docker/docker-compose.yml.example.stub'             => 'docker-compose.yml.example',
        'docker/docker-compose.yml.stub'                     => 'docker-compose.yml',
        'docker/dockerfiles/horizon/horizon.docker.stub'     => 'dockerfiles/horizon/horizon.docker',
        'docker/dockerfiles/horizon/horizon.sh.stub'         => 'dockerfiles/horizon/horizon.sh',
        'docker/dockerfiles/mysql/mysql.docker.stub'         => 'dockerfiles/mysql/mysql.docker',
        'docker/dockerfiles/mysql/my.cnf.stub'               => 'dockerfiles/mysql/my.cnf',
        'docker/dockerfiles/nginx/nginx.docker.stub'         => 'dockerfiles/nginx/nginx.docker',
        'docker/dockerfiles/nginx/vhost.conf.stub'           => 'dockerfiles/nginx/vhost.conf',
        'docker/dockerfiles/php/php.docker.stub'             => 'dockerfiles/php/php.docker',
        'docker/dockerfiles/php/limits.conf.stub'            => 'dockerfiles/php/limits.conf',
        'docker/dockerfiles/redis/redis.docker.stub'         => 'dockerfiles/redis/redis.docker',
        'docker/dockerfiles/scheduler/scheduler.docker.stub' => 'dockerfiles/scheduler/scheduler.docker',
        'docker/dockerfiles/scheduler/scheduler.sh.stub'     => 'dockerfiles/scheduler/scheduler.sh',
    ];

    public function handle()
    {
        if (is_dir(base_path('dockerfiles'))) {
            $this->error('Configuraciones docker ya fueron generadas anteriormente.');

            return;
        }
        $this->createDirectories();
        $this->exportFiles();
        $this->info('Configuraciónes docker generadas correctamente.');
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
