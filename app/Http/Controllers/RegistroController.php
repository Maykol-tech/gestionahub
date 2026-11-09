<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Registro publico de un negocio con prueba gratis de 14 dias (GEST-91). */
class RegistroController extends Controller
{
    public const DIAS_PRUEBA = 14;

    public function mostrar(Request $request): Response
    {
        return response()->view('publico.registro', [
            'planes' => $this->planes(),
            'plan' => $request->query('plan'),
        ]);
    }

    public function registrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_negocio' => ['required', 'string', 'max:120'],
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan' => ['required', 'string', 'exists:planes_saas,slug'],
            'terminos' => ['accepted'],
            'rubro' => ['required', 'in:' . implode(',', \App\Support\PlantillasRubro::claves())],
        ]);

        $plan = DB::table('planes_saas')->where('slug', $datos['plan'])->where('activo', true)->first();
        if (! $plan) {
            return back()->withErrors(['plan' => 'Ese plan ya no está disponible.'])->withInput();
        }

        $usuario = DB::transaction(function () use ($datos, $plan) {
            $ahora = now();

            $tenantDatos = [
                'nombre_negocio' => $datos['nombre_negocio'],
                'email' => $datos['email'],
                'estado' => 'activo',
                'rubro' => $datos['rubro'],
                'plan_id' => $plan->id,
                'suscripcion_hasta' => $ahora->copy()->addDays(self::DIAS_PRUEBA)->toDateString(),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            if (Schema::hasColumn('tenants', 'slug')) {
                $tenantDatos['slug'] = $this->slugUnico($datos['nombre_negocio']);
            }

            $tenantId = DB::table('tenants')->insertGetId($tenantDatos);

            // forceFill evita depender de $fillable del modelo User
            $usuario = new User();
            $usuario->forceFill([
                'tenant_id' => $tenantId,
                'name' => $datos['nombre'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'email_verified_at' => $ahora,
            ])->save();

            $usuario->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

            return $usuario;
        });

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->to(url('/admin'))
            ->with('ok', 'Tu prueba gratis de ' . self::DIAS_PRUEBA . ' días empezó. Configura tu negocio para comenzar.');
    }

    private function planes()
    {
        return DB::table('planes_saas')->where('activo', true)->orderBy('precio_mensual')
            ->get(['slug', 'nombre', 'precio_mensual']);
    }

    private function slugUnico(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'negocio';
        $slug = $base;
        $n = 2;

        while (DB::table('tenants')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
