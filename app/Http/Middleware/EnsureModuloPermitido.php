<?php

namespace App\Http\Middleware;

use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea los modulos del panel que el rol del usuario no puede ver.
 * Un repartidor que intenta entrar al panel es llevado a su app.
 */
class EnsureModuloPermitido
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $modulo = Roles::moduloDe($request);

        if (Roles::puede($usuario, $modulo)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'No tienes permiso para usar este módulo.');
        }
        if ($url = Roles::appRepartidor($usuario)) {
            return redirect($url);
        }

        abort(403, 'No tienes permiso para ver este módulo.');
    }
}