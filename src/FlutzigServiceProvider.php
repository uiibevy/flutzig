<?php

namespace Uiibevy\Flutzig;

use Illuminate\Support\ServiceProvider;

class FlutzigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(CommandRouteGenerator::class);
        }
    }
}
