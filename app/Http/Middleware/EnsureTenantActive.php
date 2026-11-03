<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $tenant = Auth::user()->tenant; 
            if ($tenant && in_array($tenant->estado, ['suspendido', 'cancelado'])) {
                abort(403, 'El tenant no está activo.');
            }
        }
        return $next($request);
    }
}