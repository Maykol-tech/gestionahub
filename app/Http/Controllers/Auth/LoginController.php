<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Support\Roles;
use App\Support\Seguridad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login del panel administrativo (sesion web).
 * Seguridad: tras N intentos fallidos el acceso se bloquea por el tiempo que el admin eligió
 * en Cuenta → Seguridad (por correo + IP). Por defecto 5 intentos y 15 minutos.
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

    public function ingresar(LoginRequest $request)
    {
        $datos = $request->credenciales();
        $reglas = Seguridad::reglasParaCorreo($datos['email']);
        $max = $reglas['intentos'];

        $clave = self::claveIntentos($datos['email'], (string) $request->ip());

        if (RateLimiter::tooManyAttempts($clave, $max)) {
            return $this->bloqueado($clave, $datos['email'], $max);
        }

        if (! Auth::attempt($datos, $request->boolean('recordar'))) {
            RateLimiter::hit($clave, $reglas['bloqueo_minutos'] * 60);

            if (RateLimiter::tooManyAttempts($clave, $max)) {
                Seguridad::registrar('bloqueado', $datos['email'], $request);

                return $this->bloqueado($clave, $datos['email'], $max);
            }
            Seguridad::registrar('fallido', $datos['email'], $request);

            $quedan = RateLimiter::remaining($clave, $max);
            $aviso = $quedan <= 2
                ? " Te queda{$this->plural($quedan, '', 'n')} {$quedan} intento{$this->plural($quedan)} antes del bloqueo de " . Seguridad::textoDuracion($reglas['bloqueo_minutos']) . '.'
                : '';

            return back()->withInput($request->only('email', 'recordar'))
                ->withErrors(['email' => 'El correo o la contraseña no son correctos.' . $aviso])
                ->with('intentos_restantes', $quedan)
            ->with('intentos_max', $max);
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
        Seguridad::registrar('ingreso', $datos['email'], $request, $usuario);
        $request->session()->regenerate();
        $request->session()->forget(PantallaBloqueoController::SESION);
        $request->session()->put(PantallaBloqueoController::ACTIVIDAD, now()->timestamp);

        return redirect()->intended($destino);
    }

    public function salir(Request $request)
    {
        if ($u = Auth::user()) {
            Seguridad::registrar('salida', (string) $u->email, $request, $u);
        }
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

    /** Respuesta cuando el acceso esta bloqueado: mensaje con el tiempo que falta. */
    private function bloqueado(string $clave, string $email, int $max)
    {
        $segundos = max(1, RateLimiter::availableIn($clave));

        return back()->withInput(['email' => $email])
            ->withErrors(['email' => 'Por seguridad bloqueamos el acceso tras ' . $max
                . ' intentos fallidos. Podrás volver a intentar en ' . self::formatoTiempo($segundos) . '.'])
            ->with('bloqueo_segundos', $segundos)
            ->with('bloqueo_total', max($segundos, Seguridad::reglasParaCorreo($email)['bloqueo_minutos'] * 60))
            ->with('intentos_max', $max);
    }

    /** 845 -> "14 min 05 s"; 5400 -> "1 h 30 min" */
    public static function formatoTiempo(int $segundos): string
    {
        if ($segundos >= 3600) {
            $h = intdiv($segundos, 3600);
            $m = intdiv($segundos % 3600, 60);

            return $m ? sprintf('%d h %02d min', $h, $m) : sprintf('%d h', $h);
        }
        $min = intdiv($segundos, 60);
        $seg = $segundos % 60;

        return $min > 0 ? sprintf('%d min %02d s', $min, $seg) : sprintf('%d s', $seg);
    }

    private function plural(int $n, string $singular = '', string $plural = 's'): string
    {
        return $n === 1 ? $singular : $plural;
    }
}
