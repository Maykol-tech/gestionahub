<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login del panel administrativo (sesion web).
 * Seguridad: despues de 5 intentos fallidos el acceso se bloquea 15 minutos
 * (por correo + IP) y la pantalla muestra el tiempo que falta.
 * Despues de entrar, cada rol va a su propia pantalla.
 */
class LoginController extends Controller
{
    public const MAX_INTENTOS = 5;

    public const MINUTOS_BLOQUEO = 15;

    public function mostrar()
    {
        return view('auth.login', [
            'maxIntentos' => self::MAX_INTENTOS,
            'minutosBloqueo' => self::MINUTOS_BLOQUEO,
        ]);
    }

    public function ingresar(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ], [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $clave = self::claveIntentos($datos['email'], (string) $request->ip());

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            return $this->bloqueado($clave, $datos['email']);
        }

        if (! Auth::attempt($datos, $request->boolean('recordar'))) {
            RateLimiter::hit($clave, self::MINUTOS_BLOQUEO * 60);

            if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
                return $this->bloqueado($clave, $datos['email']);
            }

            $quedan = RateLimiter::remaining($clave, self::MAX_INTENTOS);
            $aviso = $quedan <= 2
                ? " Te queda{$this->plural($quedan, '', 'n')} {$quedan} intento{$this->plural($quedan)} antes del bloqueo de " . self::MINUTOS_BLOQUEO . ' minutos.'
                : '';

            return back()->withInput($request->only('email', 'recordar'))
                ->withErrors(['email' => 'El correo o la contraseña no son correctos.' . $aviso])
                ->with('intentos_restantes', $quedan);
        }

        $usuario = Auth::user();

        if (! $usuario->tenant_id) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tu usuario no está asociado a ningún negocio.']);
        }

        if (! Roles::rol($usuario)) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tu usuario aún no tiene un rol asignado. Pide al administrador que te lo asigne.']);
        }

        $destino = Roles::inicio($usuario);
        if (! $destino) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tu usuario de repartidor no tiene una app asignada.']);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();
        $request->session()->forget(PantallaBloqueoController::SESION);
        $request->session()->put(PantallaBloqueoController::ACTIVIDAD, now()->timestamp);

        return redirect()->intended($destino);
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Cerraste sesión correctamente.');
    }

    /** Clave del contador de intentos: correo en minusculas + IP. */
    public static function claveIntentos(string $email, string $ip): string
    {
        return 'login:' . Str::lower(trim($email)) . '|' . $ip;
    }

    /** Respuesta cuando el acceso esta bloqueado: mensaje con minutos y segundos que faltan. */
    private function bloqueado(string $clave, string $email)
    {
        $segundos = max(1, RateLimiter::availableIn($clave));

        return back()->withInput(['email' => $email])
            ->withErrors(['email' => 'Por seguridad bloqueamos el acceso tras ' . self::MAX_INTENTOS
                . ' intentos fallidos. Podrás volver a intentar en ' . self::formatoTiempo($segundos) . '.'])
            ->with('bloqueo_segundos', $segundos);
    }

    /** 845 -> "14 min 05 s" */
    public static function formatoTiempo(int $segundos): string
    {
        $min = intdiv($segundos, 60);
        $seg = $segundos % 60;

        return $min > 0 ? sprintf('%d min %02d s', $min, $seg) : sprintf('%d s', $seg);
    }

    private function plural(int $n, string $singular = '', string $plural = 's'): string
    {
        return $n === 1 ? $singular : $plural;
    }
}
