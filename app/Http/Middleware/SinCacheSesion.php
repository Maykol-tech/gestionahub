<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Botón "Atrás": las pantallas de acceso y las del panel no se guardan en la caché del navegador.
 * Así, al volver con "Atrás" el navegador pide la página de nuevo: si ya hay sesión, el middleware
 * "guest" manda al panel en vez de mostrar el login; y tras cerrar sesión no se ve el panel guardado.
 */
class SinCacheSesion
{
    private const RUTAS_ACCESO = ['login', 'registro', 'password.*', 'superadmin.*', 'admin.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() || $request->routeIs(...self::RUTAS_ACCESO)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
