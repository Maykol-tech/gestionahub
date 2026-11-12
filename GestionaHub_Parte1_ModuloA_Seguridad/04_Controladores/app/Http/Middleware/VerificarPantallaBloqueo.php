<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\PantallaBloqueoController as Bloqueo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras la pantalla esta bloqueada el panel no responde datos ni acepta cambios (HTTP 423).
 * Las paginas completas si cargan, pero el layout las muestra cubiertas por la pantalla de bloqueo.
 * Tambien bloquea si el usuario vuelve despues de mas minutos de inactividad que los permitidos.
 */
class VerificarPantallaBloqueo
{
    /** Rutas que deben funcionar con la pantalla bloqueada. */
    private const LIBRES = ['bloqueo.bloquear', 'bloqueo.desbloquear', 'bloqueo.actividad', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin', 'admin/*') || ! $request->user() || $request->routeIs(...self::LIBRES)) {
            return $next($request);
        }

        $sesion = $request->session();
        $ultima = (int) $sesion->get(Bloqueo::ACTIVIDAD, 0);
        $vencida = $ultima > 0 && (now()->timestamp - $ultima) > (Bloqueo::minutos() * 60 + 30);

        if ($vencida) {
            $sesion->put(Bloqueo::SESION, true);
        }

        if ($sesion->get(Bloqueo::SESION)) {
            $esPaginaCompleta = $request->isMethod('GET') && ! $request->ajax() && ! $request->expectsJson() && ! $request->hasHeader('X-GH-Nav');

            if (! $esPaginaCompleta) {
                return response()->json([
                    'bloqueado' => true,
                    'message' => 'La pantalla está bloqueada. Ingresa tu contraseña para continuar.',
                ], 423);
            }

            return $next($request);
        }

        // Las consultas automaticas (recargas en segundo plano) no cuentan como actividad
        $esSondeo = $request->isMethod('GET') && ($request->ajax() || $request->expectsJson());
        if (! $esSondeo) {
            $sesion->put(Bloqueo::ACTIVIDAD, now()->timestamp);
        }

        return $next($request);
    }
}
