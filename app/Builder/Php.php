<?php

namespace App\Builder;

class Php extends Command
{
    public function getProgramName() : string
    {
        return 'app php';
    }

    public function makeWrapper() : ?Command
    {
        return DockerComposeExec::make()->setUser(env('FWD_ASUSER'));
    }

    public function getDockerComposeExec() : DockerComposeExec
    {
        return $this->wrapper;
    }

    public function getDefaultArgs(): array
    {
        return ['-v'];
    }
}
