<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller {
    public function showLoginForm() {
        if (Auth::check()) return redirect()->route('admin.dashboard');
        return view('auth.login');
    }

    public function mostrar() {
        return $this->showLoginForm();
    }

    // Método que exige tu archivo de rutas
    public function ingresar(LoginRequest $request) {
        $key = Str::lower($request->input('email')) . '|' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            return back()->withErrors(['email' => "Demasiados intentos. Intenta de nuevo en {$minutes} minutos."])->withInput();
        }

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            
            if (auth()->user()->tenant && auth()->user()->tenant->estado === 'suspendido') {
                Auth::logout();
                return back()->withErrors(['email' => 'Tu cuenta ha sido suspendida.']);
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($key, 900);
        return back()->withErrors(['email' => 'Las credenciales no coinciden con nuestros registros.'])->withInput();
    }

    // Alias por si acaso
    public function login(LoginRequest $request) {
        return $this->ingresar($request);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('status', 'Cerraste sesión correctamente.');
    }
}