<?php

namespace Uiibevy\Flutzig;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CommandRouteGenerator extends Command
{
    protected $signature = "flutzig:generate {path? : Path to the generated Json file. Default: `storage/public/flutzig/routes.json`.} {--url=} {--group=}";

    protected $description = "Generate a json file containing Flutzig's routes and configuration";

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        // coming soon
    }
}
