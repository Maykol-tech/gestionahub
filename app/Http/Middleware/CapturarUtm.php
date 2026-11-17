<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GEST-99: si la visita llega con utm_source, utm_medium o utm_campaign (o gclid/fbclid),
 * se guardan en una cookie por 30 días. Así el lead o el registro de días después sigue
 * sabiendo de qué campaña vino (primer contacto: no se pisa con visitas sin UTM).
 */
class CapturarUtm
{
    public const COOKIE = 'gh_utm';
    public const MINUTOS = 60 * 24 * 30;
    public const CLAVES = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'];

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (! $request->isMethod('GET')) {
            return $respuesta;
        }
        $nuevos = [];
        foreach (self::CLAVES as $c) {
            $v = $request->query($c);
            if (is_string($v) && trim($v) !== '') {
                $nuevos[$c] = mb_substr(strip_tags(trim($v)), 0, 100);
            }
        }
        if ($nuevos && ! $request->cookie(self::COOKIE) && method_exists($respuesta, 'withCookie')) {
            $nuevos['landing'] = mb_substr($request->path(), 0, 100);
            $nuevos['fecha'] = now()->toDateString();
            $respuesta->withCookie(cookie(self::COOKIE, json_encode($nuevos), self::MINUTOS, null, null, null, true, false, 'Lax'));
        }

        return $respuesta;
    }

    /** UTM guardados (los del formulario mandan sobre los de la cookie). */
    public static function leer(Request $request): array
    {
        $cookie = json_decode((string) $request->cookie(self::COOKIE), true);
        $cookie = is_array($cookie) ? $cookie : [];
        $form = array_filter($request->only(['utm_source', 'utm_medium', 'utm_campaign']), fn ($v) => is_string($v) && $v !== '');

        return array_map(fn ($v) => mb_substr((string) $v, 0, 100), array_intersect_key($form + $cookie, array_flip(self::CLAVES)));
    }
}
