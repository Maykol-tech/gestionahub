<?php

namespace App\Http\Middleware;

use App\Support\ModulosPrototipo;
use Closure;
use Illuminate\Http\Request;

/** Responde 404 en los módulos que no están en el prototipo (ver App\Support\ModulosPrototipo). */
class SoloModulosDelPrototipo
{
    public function handle(Request $request, Closure $next)
    {
        abort_if(ModulosPrototipo::bloqueada($request->route()?->getName()), 404);

        return $next($request);
    }
}
