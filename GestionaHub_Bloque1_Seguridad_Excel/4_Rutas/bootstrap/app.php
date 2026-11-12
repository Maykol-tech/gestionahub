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
        // Produccion: el usuario nunca ve la pantalla de error de Laravel.
        // Las paginas usan resources/views/errors/*.blade.php y las peticiones JSON reciben un mensaje claro.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson() || config('app.debug')) {
                return null; // Laravel usa las vistas de errors/ (o el detalle si APP_DEBUG=true)
            }
            if ($e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null; // respuestas 422 y 401 normales
            }
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return response()->json(['message' => 'Tu sesión expiró. Recarga la página.'], 419);
            }
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'El registro ya no existe o no pertenece a tu negocio.'], 404);
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $codigo = $e->getStatusCode();
                $mensajes = [403 => 'No tienes permiso para esta acción.', 404 => 'No encontramos lo que buscas.', 429 => 'Demasiadas solicitudes. Espera un momento.'];

                return response()->json(['message' => $e->getMessage() ?: ($mensajes[$codigo] ?? 'No se pudo completar la acción.')], $codigo, $e->getHeaders());
            }

            return response()->json(['message' => 'Ocurrió un error inesperado. Ya quedó registrado; intenta de nuevo.'], 500);
        });

        // No llenar el log con errores esperados
        $exceptions->dontReport([\Illuminate\Session\TokenMismatchException::class]);
    })->create();