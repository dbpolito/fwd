<?php

namespace App\Tasks;

use App\Builder\Builder;
use App\Environment;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Deploy extends Task
{
    protected $file;
    protected $deploy;

    public function run(...$args): int
    {
        try {
            $commands = [
                [$this, 'createReleaseFile'],
                [$this, 'sendReleaseFile'],
                [$this, 'building'],
            ];

            if ($exitCode = $this->runCallables($commands)) {
                return $exitCode;
            }

            $this->command->info(sprintf("Access URL: http://%s.fwd.tools", $this->deploy->id));

            return 0;
        } finally {
            $environment = resolve(Environment::class);
            File::delete($environment->getContextFile($this->file));
        }
    }

    public function createReleaseFile() : int
    {
        return $this->runTask('Create Release File', function () {
            $this->file = sprintf('%s.tgz', Str::random(10));

            $this->runCommandWithoutOutput(
                Builder::make('git', 'archive', '--format=tar.gz', 'HEAD', '-o', $this->file)
            );

            return 0;
        });
    }

    public function sendReleaseFile() : int
    {
        return $this->runTask('Send Release File', function () {
            $environment = resolve(Environment::class);
            $this->deploy = json_decode((new Client)->post('https://fwd.tools/api/deploy', [
                'multipart' => [[
                    'name'     => 'deploy',
                    'contents' => fopen($environment->getContextFile($this->file), 'r'),
                ]]
            ])->getBody()->getContents());

            return 0;
        });
    }

    public function building() : int
    {
        return $this->runTask('Building', function () {
            return $this->runCallableWaitFor(function () {
                $url = sprintf('https://fwd.tools/api/deploy/%s/status', $this->deploy->id);
                $response = json_decode((new Client)->get($url)->getBody()->getContents());

                if ($response->status === 'failed') {
                    throw new \Exception('Failed');
                }

                return $response->status === 'success' ? 0 : 1;
            }, 600);
        });
    }
}
