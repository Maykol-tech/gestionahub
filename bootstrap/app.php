<?php

use Illuminate\Foundation\Application;
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
    $middleware->redirectUsersTo('/admin');
    $middleware->alias([
        'tenant.active' => \App\Http\Middleware\EnsureTenantActive::class,
        'modulo.permitido' => \App\Http\Middleware\EnsureModuloPermitido::class,
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'pantalla.bloqueo' => \App\Http\Middleware\VerificarPantallaBloqueo::class,
    ]);
    // Pantalla de bloqueo por inactividad: se revisa en todas las rutas /admin
    $middleware->appendToGroup('web', \App\Http\Middleware\VerificarPantallaBloqueo::class);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();