<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Pantalla de bloqueo por inactividad (suspension).
 * Tras unos minutos sin actividad el panel se cubre y pide la contraseña;
 * la sesion NO se cierra, solo queda suspendida hasta desbloquear.
 * El estado vive en la sesion del servidor, asi recargar la pagina no salta el bloqueo.
 */
class PantallaBloqueoController extends Controller
{
    /** Minutos sin actividad antes de bloquear (entre 5 y 10). */
    public const MINUTOS = 7;

    /** Intentos de contraseña antes de cerrar la sesion por seguridad. */
    public const MAX_INTENTOS = 5;

    public const SESION = 'pantalla_bloqueada';

    public const ACTIVIDAD = 'ultima_actividad';

    public static function minutos(): int
    {
        return max(5, min(10, (int) config('app.bloqueo_minutos', self::MINUTOS)));
    }

    /** El navegador avisa que el usuario esta inactivo: se bloquea en el servidor. */
    public function bloquear(Request $request): JsonResponse
    {
        $request->session()->put(self::SESION, true);

        return response()->json(['bloqueado' => true]);
    }

    /** Latido de actividad: mantiene viva la sesion mientras el usuario trabaja. */
    public function actividad(Request $request): JsonResponse
    {
        if ($request->session()->get(self::SESION)) {
            return response()->json(['bloqueado' => true, 'message' => 'Pantalla bloqueada.'], 423);
        }

        $request->session()->put(self::ACTIVIDAD, now()->timestamp);

        return response()->json(['bloqueado' => false]);
    }

    public function desbloquear(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:200']], [
            'password.required' => 'Escribe tu contraseña para continuar.',
        ]);

        $usuario = $request->user();
        $clave = 'desbloqueo:' . $usuario->id;

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            return $this->cerrarSesion($request, $clave);
        }

        if (! Hash::check($request->input('password'), $usuario->password)) {
            RateLimiter::hit($clave, LoginController::MINUTOS_BLOQUEO * 60);

            if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
                return $this->cerrarSesion($request, $clave);
            }

            $quedan = RateLimiter::remaining($clave, self::MAX_INTENTOS);

            return response()->json([
                'message' => 'Contraseña incorrecta.',
                'errors' => ['password' => ['Contraseña incorrecta. ' . ($quedan === 1 ? 'Te queda 1 intento.' : "Te quedan {$quedan} intentos.")]],
                'intentos_restantes' => $quedan,
            ], 422);
        }

        RateLimiter::clear($clave);
        $request->session()->forget(self::SESION);
        $request->session()->put(self::ACTIVIDAD, now()->timestamp);

        return response()->json(['bloqueado' => false, 'message' => 'Bienvenido de nuevo, ' . strtok((string) $usuario->name, ' ') . '.']);
    }

    /** Demasiados intentos en la pantalla de bloqueo: por seguridad se cierra la sesion. */
    private function cerrarSesion(Request $request, string $clave): JsonResponse
    {
        RateLimiter::clear($clave);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('estado', 'Cerramos tu sesión por seguridad tras varios intentos fallidos. Ingresa de nuevo.');

        return response()->json(['cerrar_sesion' => true, 'redirect' => route('login')], 401);
    }
}
