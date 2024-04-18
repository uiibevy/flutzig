<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Uiibevy\Flutzig\Flutzig;

test('it can generate routes default', function () {
    Route::get('home', $this->noop())->name('home');
    Route::post('auth/login', $this->noop())->name('login');
    Route::patch('auth/register', $this->noop())->name('register');
    Route::put('auth/forgot-password', $this->noop())->name('auth.forgot-password');

    $flutzig = new Flutzig();

    $expected = [
        "url" => "http://localhost",
        "port" => null,
        "defaults" => [],
        "routes" => [
            "home" => [
                "uri" => "home",
                "methods" => [
                    0 => "GET",
                    1 => "HEAD"
                ]
            ],
            "login" => [
                "uri" => "auth/login",
                "methods" => ["POST"],
            ],
            "register" => [
                "uri" => "auth/register",
                "methods" => ["PATCH"],
            ],
            "auth.forgot-password" => [
                "uri" => "auth/forgot-password",
                "methods" => ["PUT"],
            ],
        ]
    ];

    $result = $flutzig->toArray();

    $this->assertNotNull($result);
    $this->assertEquals($expected, $result);
});
