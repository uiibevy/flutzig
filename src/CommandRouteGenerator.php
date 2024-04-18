<?php

namespace Uiibevy\Flutzig;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Uiibevy\Flutzig\Output\File;

class CommandRouteGenerator extends Command
{
    protected $signature = "flutzig:generate {path? : Path to the generated Json file. Default: `storage/public/flutzig/routes.json`.} {--url=} {--group=}";

    protected $description = "Generate a json file containing Flutzig's routes and configuration";

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): void
    {
        $group = $this->option('group');
        $url = $this->hasOption('url') ? $this->option('url') : null;

        $flutzig = Flutzig::from($group, $url);

        $path = $this->argument('path') ?? config(
            'flutzig.output.path',
            'storage/public/flutzig/routes.json'
        );

        if ($this->files->isDirectory(base_path($path))) {
            $path .= '/flutzig';
        } else {
            $this->makeDirectory($path);
        }

        $name = preg_replace('/(\.d)?\.json$/', '', $path);

        $output = config('flutzig.output.file', File::class);

        $this->files->put(base_path("$name.json"), new $output($flutzig));

        $this->info('Files generated!');
    }

    /**
     * Creates a directory at the given path if it doesn't already exist.
     *
     * @param  string  $path  The path of the directory to create.
     * @return void
     */
    private function makeDirectory(string $path): void
    {
        if ($this->files->isDirectory(dirname(base_path($path)))) {
            return;
        }

        $this->files->makeDirectory(
            dirname(base_path($path)),
            0755, true, true
        );
    }
}
