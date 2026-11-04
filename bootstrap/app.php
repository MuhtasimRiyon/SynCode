<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->trustHosts(at: fn () => app()->isProduction()
            ? ['^'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost', '/').'$']
            : ['^.*$'], subdomains: false);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
