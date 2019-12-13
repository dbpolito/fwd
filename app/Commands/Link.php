<?php

namespace App\Commands;

use App\Builder\Docker;
use App\Builder\Builder;
use App\Builder\Escaped;
use Illuminate\Support\Str;
use App\Builder\DockerCompose;

class Link extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'link {file}
                            {--proxy=nginx : Host reserve proxy}
                            {--service=http : The service to link}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Link service with host nginx reverse proxy.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exitCode = $this->commandExecutor->runQuietly(
            DockerCompose::makeWithDefaultArgs('ps', '-q', $this->option('service'))
        );

        if ($exitCode) {
            $this->error('Failed to get container service id.');
            return 1;
        }

        $id = $this->commandExecutor->getOutputBuffer();

        $exitCode = $this->commandExecutor->runQuietly(Docker::make(
            'inspect',
            '-f',
            Escaped::make('{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'),
            $id
        ));

        if ($exitCode) {
            $this->error('Failed to get container ip.');
            return 1;
        }

        $ip = $this->commandExecutor->getOutputBuffer();
        $file = $this->argument('file');

        if ($this->option('proxy') === 'nginx') {
            $exitCode = $this->commandExecutor->runQuietly(Builder::make(
                'sudo',
                'sed',
                '-i',
                Escaped::make("s/proxy_pass .*/proxy_pass http:\\/\\/{$ip};/"),
                Str::startsWith($file, '/') ? $file : "/etc/nginx/sites-enabled/{$file}"
            ));

            if (! $exitCode) {
                $this->error('Failed to update the nginx vhost file.');
                return 1;
            }

            $exitCode = $this->commandExecutor->runQuietly(Builder::make(
                'sudo',
                'service',
                'nginx',
                'reload'
            ));

            if (! $exitCode) {
                $this->error('Failed to reload nginx service.');
                return 1;
            }
        }
    }
}
