<?php

namespace Tests;

use Closure;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Uiibevy\Flutzig\FlutzigServiceProvider;

class TestCase extends OrchestraTestCase
{
    public function router(): Route
    {
        return app('router');
    }

    protected function getPackageProviders($app): array
    {
        return [FlutzigServiceProvider::class];
    }

    protected function noop(): Closure
    {
        return function () {
            return '';
        };
    }
}
