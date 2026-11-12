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
 * Despues de entrar, cada rol va a su propia pantalla.
 */
class LoginController extends Controller
{
    public function mostrar()
    {
        return view('auth.login');
    }

    public function ingresar(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        // Maximo 5 intentos por minuto por correo e IP
        $clave = Str::lower($datos['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelve a intentar en ' . RateLimiter::availableIn($clave) . ' segundos.',
            ]);
        }

        if (! Auth::attempt($datos, $request->boolean('recordar'))) {
            RateLimiter::hit($clave, 60);
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
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

        return redirect()->intended($destino);
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Cerraste sesión correctamente.');
    }
}