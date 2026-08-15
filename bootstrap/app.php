<?php

use App\Http\Middleware\EnsureStudent;
use Illuminate\Foundation\Application;
use App\Http\Middleware\HandleOversizedUpload;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'student' => EnsureStudent::class,
        ]);

        /*
         * Va antes de la verificación de CSRF: si PHP descartó el cuerpo por
         * tamaño, el token tampoco llegó, y el 419 taparía el motivo real.
         */
        $middleware->web(prepend: [
            HandleOversizedUpload::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
