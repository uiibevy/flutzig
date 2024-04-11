<?php

namespace Uiibevy\Flutzig;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FlutzigServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if($this->app->runningInConsole()){
            $this->commands(CommandRouteGenerator::class);
        }
    }
}
