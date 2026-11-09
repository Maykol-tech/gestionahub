<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el negocio esta suspendido, cancelado o no existe, corta el acceso.
 * En el panel cierra la sesion; en la API responde 403.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario) {
            $tenant = $usuario->tenant;

            if (! $tenant || in_array($tenant->estado, ['suspendido', 'cancelado'], true)) {
                if ($request->is('api/*') || $request->expectsJson()) {
                    abort(403, 'El negocio no está activo.');
                }

                auth('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'El negocio no está activo. Contacta con GestionaHub.']);
            }
        }

        return $next($request);
    }
}