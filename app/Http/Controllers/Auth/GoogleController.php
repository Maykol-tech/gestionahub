<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RegistroController;
use App\Http\Middleware\CapturarUtm;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

/**
 * GEST-92: ingresar o crear la cuenta con Google.
 * - Si el correo ya existe, se vincula y entra (con las mismas reglas que el login normal).
 * - Si es nuevo, se crea el negocio con 14 días de prueba y pasa al asistente de inicio.
 */
class GoogleController extends Controller
{
    public static function disponible(): bool
    {
        return (bool) (config('services.google.client_id') && config('services.google.client_secret'));
    }

    public function redirigir(Request $request): RedirectResponse
    {
        if (! self::disponible()) {
            return redirect()->route('login')->withErrors(['email' => 'El ingreso con Google aún no está configurado.']);
        }
        if ($request->query('plan')) {
            session(['google_plan' => (string) $request->query('plan')]);
        }

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function volver(Request $request): RedirectResponse
    {
        if (! self::disponible()) {
            return redirect()->route('login');
        }
        try {
            $g = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::info('Google login cancelado o inválido', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'No pudimos ingresar con Google. Intenta de nuevo.']);
        }

        $email = strtolower((string) $g->getEmail());
        if ($email === '' || ! ($g->user['email_verified'] ?? $g->user['verified_email'] ?? true)) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta de Google no tiene un correo verificado.']);
        }

        $usuario = User::where('google_id', $g->getId())->first() ?? User::where('email', $email)->first();
        $nuevo = false;

        if ($usuario) {
            $usuario->forceFill(['google_id' => $g->getId(), 'avatar_url' => $g->getAvatar() ?: $usuario->avatar_url])->save();
        } else {
            $usuario = $this->crearCuenta($g->getName() ?: Str::before($email, '@'), $email, $g->getId(), $g->getAvatar());
            $nuevo = true;
            RegistroController::registrarLead($usuario, $usuario->tenant?->nombre_negocio ?? '', 'google', CapturarUtm::leer($request));
        }

        if (! $usuario->tenant_id || ! Roles::rol($usuario) || ! ($destino = Roles::inicio($usuario))) {
            return redirect()->route('login')->withErrors(['email' => 'Tu usuario no tiene un negocio o rol asignado. Pide ayuda al administrador.']);
        }

        Auth::login($usuario, true);
        $request->session()->regenerate();
        $request->session()->put(PantallaBloqueoController::ACTIVIDAD, now()->timestamp);

        if ($nuevo) {
            session()->flash('evento', 'registro_completo');
            session()->flash('evento_datos', ['metodo' => 'google']);

            return redirect()->route('admin.bienvenida')->with('ok', 'Tu prueba gratis de ' . RegistroController::DIAS_PRUEBA . ' días empezó. Configura tu negocio para comenzar.');
        }

        return redirect()->intended($destino);
    }

    private function crearCuenta(string $nombre, string $email, string $googleId, ?string $avatar): User
    {
        $slugPlan = session()->pull('google_plan');
        $plan = DB::table('planes_saas')->where('activo', true)->when($slugPlan, fn ($q) => $q->where('slug', $slugPlan))->orderBy('precio_mensual')->first()
            ?? DB::table('planes_saas')->where('activo', true)->orderBy('precio_mensual')->first();

        return DB::transaction(function () use ($nombre, $email, $googleId, $avatar, $plan) {
            $ahora = now();
            $negocio = 'Negocio de ' . Str::of($nombre)->explode(' ')->first();
            $datos = [
                'nombre_negocio' => $negocio, 'email' => $email, 'estado' => 'trial',
                'trial_ends_at' => $ahora->copy()->addDays(RegistroController::DIAS_PRUEBA),
                'suscripcion_hasta' => $ahora->copy()->addDays(RegistroController::DIAS_PRUEBA)->toDateString(),
                'plan_id' => $plan->id ?? null, 'created_at' => $ahora, 'updated_at' => $ahora,
            ];
            if (Schema::hasColumn('tenants', 'rubro')) {
                $datos['rubro'] = 'otro';
            }
            if (Schema::hasColumn('tenants', 'slug')) {
                $base = Str::slug($negocio) ?: 'negocio';
                $slug = $base;
                $n = 2;
                while (DB::table('tenants')->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $n++;
                }
                $datos['slug'] = $slug;
            }
            // El correo del negocio es único: si ya lo usa otro negocio, se guarda con un sufijo
            if (DB::table('tenants')->where('email', $email)->exists()) {
                $datos['email'] = Str::before($email, '@') . '+' . Str::random(4) . '@' . Str::after($email, '@');
            }
            $tenantId = DB::table('tenants')->insertGetId($datos);

            $u = new User();
            $u->forceFill([
                'tenant_id' => $tenantId, 'name' => $nombre, 'email' => $email,
                'password' => Hash::make(Str::random(40)), 'email_verified_at' => $ahora,
                'google_id' => $googleId, 'avatar_url' => $avatar,
            ])->save();
            $u->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

            return $u;
        });
    }
}
