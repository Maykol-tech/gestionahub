<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use App\Support\AjustesNegocio;
use App\Support\Seguridad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Cuenta → Seguridad: el admin elige cuántos intentos fallidos se permiten antes de bloquear,
 * cuánto dura el bloqueo (minutos u horas) y la pantalla de bloqueo por inactividad.
 * También ve quién está bloqueado ahora (y lo puede desbloquear) y el historial de accesos.
 */
class SeguridadController extends DashboardController
{
    private function tenant(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function pagina()
    {
        $this->compartirLayout();
        $reglas = Seguridad::reglas($this->tenant());
        $hayLog = Schema::hasTable('accesos_log');

        $historial = $hayLog
            ? DB::table('accesos_log')->where('tenant_id', $this->tenant())->orderByDesc('id')->limit(60)->get()
            : collect();

        // Bloqueados ahora: correos + IP con bloqueo vigente en el contador de intentos
        $bloqueados = collect();
        if ($hayLog) {
            $bloqueados = DB::table('accesos_log')->where('tenant_id', $this->tenant())->where('resultado', 'bloqueado')
                ->where('created_at', '>=', now()->subMinutes($reglas['bloqueo_minutos']))
                ->orderByDesc('id')->get()->unique(fn ($r) => $r->email . '|' . $r->ip)
                ->map(function ($r) use ($reglas) {
                    $clave = LoginController::claveIntentos($r->email, (string) $r->ip);
                    if (! RateLimiter::tooManyAttempts($clave, $reglas['intentos'])) {
                        return null;
                    }
                    $r->resta = LoginController::formatoTiempo(max(1, RateLimiter::availableIn($clave)));

                    return $r;
                })->filter()->values();
        }

        $hoy = $hayLog ? DB::table('accesos_log')->where('tenant_id', $this->tenant())->where('created_at', '>=', today()) : null;

        return view('admin.modulos.seguridad', [
            'titulo' => 'Seguridad',
            'modulo' => 'seguridad',
            'reglas' => $reglas,
            'limites' => Seguridad::LIMITES,
            'historial' => $historial,
            'bloqueados' => $bloqueados,
            'ingresosHoy' => $hoy ? (clone $hoy)->where('resultado', 'ingreso')->count() : 0,
            'fallidosHoy' => $hoy ? (clone $hoy)->whereIn('resultado', ['fallido', 'bloqueado'])->count() : 0,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        [$minI, $maxI] = Seguridad::LIMITES['intentos'];
        [$minA, $maxA] = Seguridad::LIMITES['inactividad'];
        $datos = $request->validate([
            'intentos' => ['required', 'integer', "between:{$minI},{$maxI}"],
            'bloqueo_valor' => ['required', 'integer', 'min:1', 'max:' . ($request->input('bloqueo_unidad') === 'horas' ? 24 : 1440)],
            'bloqueo_unidad' => ['required', 'in:minutos,horas'],
            'inactividad_activa' => ['nullable', 'boolean'],
            'inactividad_minutos' => ['required', 'integer', "between:{$minA},{$maxA}"],
        ], [
            'intentos.between' => "Los intentos permitidos deben ser entre {$minI} y {$maxI}.",
            'bloqueo_valor.max' => $request->input('bloqueo_unidad') === 'horas' ? 'El bloqueo puede durar como máximo 24 horas.' : 'El bloqueo puede durar como máximo 1440 minutos (24 horas).',
            'bloqueo_valor.min' => 'El bloqueo debe durar al menos 1.',
            'inactividad_minutos.between' => "La inactividad debe ser entre {$minA} y {$maxA} minutos.",
            'required' => 'Completa el campo :attribute.',
            'integer' => 'El campo :attribute debe ser un número entero.',
        ], [
            'intentos' => 'intentos permitidos', 'bloqueo_valor' => 'tiempo de bloqueo',
            'inactividad_minutos' => 'minutos de inactividad',
        ]);

        AjustesNegocio::guardar($this->tenant(), [
            'seg_intentos' => (int) $datos['intentos'],
            'seg_bloqueo_valor' => (int) $datos['bloqueo_valor'],
            'seg_bloqueo_unidad' => $datos['bloqueo_unidad'],
            'seg_inactividad_activa' => $request->boolean('inactividad_activa'),
            'seg_inactividad_minutos' => (int) $datos['inactividad_minutos'],
        ]);

        return redirect()->route('admin.seguridad')->with('ok', 'Reglas de seguridad guardadas.');
    }

    /** Quita el bloqueo de un correo + IP antes de que termine el tiempo. */
    public function desbloquear(Request $request): RedirectResponse
    {
        $datos = $request->validate(['email' => ['required', 'string', 'max:150'], 'ip' => ['nullable', 'string', 'max:45']]);
        $email = Str::lower(trim($datos['email']));

        $existe = Schema::hasTable('accesos_log') && DB::table('accesos_log')->where('tenant_id', $this->tenant())
            ->where('email', $email)->where('ip', $datos['ip'] ?? null)->exists();
        abort_unless($existe, 404);

        RateLimiter::clear(LoginController::claveIntentos($email, (string) ($datos['ip'] ?? '')));
        DB::table('accesos_log')->insert([
            'tenant_id' => $this->tenant(), 'user_id' => auth()->id(), 'email' => $email, 'ip' => $datos['ip'] ?? null,
            'navegador' => 'Desbloqueado por ' . Str::limit((string) auth()->user()->name, 80, ''), 'resultado' => 'desbloqueado', 'created_at' => now(),
        ]);

        return redirect()->route('admin.seguridad')->with('ok', $email . ' ya puede volver a ingresar.');
    }
}
