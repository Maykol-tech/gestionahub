<?php

namespace App\Http\Middleware;

use App\Support\Prueba;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GEST-91: cuando la prueba gratis venció (o el cobro automático falló y pasó la gracia),
 * el panel queda en solo lectura: se puede mirar todo, pero no crear, editar ni borrar.
 * Pagar el plan sigue funcionando.
 */
class SoloLecturaVencida
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (! $usuario || ! $usuario->tenant_id || $request->isMethodSafe()
            || ! $request->is('admin', 'admin/*', 'api/*') || $request->routeIs(...Prueba::RUTAS_PERMITIDAS)) {
            return $next($request);
        }

        if ($usuario->hasRole('superadmin')) {
            return $next($request); // el dueño de la plataforma nunca queda en solo lectura
        }
        $estado = Prueba::de($usuario->tenant);
        if (! $estado['solo_lectura']) {
            return $next($request);
        }

        $mensaje = $estado['motivo'] === 'pago'
            ? 'No pudimos cobrar tu plan. Tu panel está en solo lectura hasta que actualices el pago.'
            : 'Tu prueba gratis terminó. Tu información está guardada; elige un plan para seguir guardando cambios.';

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json(['message' => $mensaje, 'solo_lectura' => true, 'pagar' => route('admin.modulo', 'planes')], 402);
        }

        return redirect()->route('admin.modulo', 'planes')->with('solo_lectura', $mensaje);
    }
}
