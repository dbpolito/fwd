<?php

namespace App\Commands;

use App\Tasks\Deploy as DeployBuilder;

class Deploy extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'deploy';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Deploy your app to fwd.tools.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return DeployBuilder::make($this)->run();
    }
}
