<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Reglas de seguridad del acceso que el admin elige en Cuenta → Seguridad:
 * intentos fallidos antes de bloquear, cuánto dura el bloqueo y el bloqueo de pantalla por inactividad.
 */
class Seguridad
{
    public const LIMITES = [
        'intentos' => [3, 10],
        'minutos' => [1, 1440],      // hasta 24 horas
        'inactividad' => [1, 120],
    ];

    /** Reglas del negocio (o las de por defecto si no hay negocio). */
    public static function reglas(?int $tenantId): array
    {
        $a = $tenantId ? AjustesNegocio::leer($tenantId) : AjustesNegocio::POR_DEFECTO;
        $valor = max(1, (int) $a['seg_bloqueo_valor']);
        $minutos = $a['seg_bloqueo_unidad'] === 'horas' ? $valor * 60 : $valor;

        return [
            'intentos' => self::entre((int) $a['seg_intentos'], self::LIMITES['intentos']),
            'bloqueo_minutos' => self::entre($minutos, self::LIMITES['minutos']),
            'bloqueo_valor' => $valor,
            'bloqueo_unidad' => $a['seg_bloqueo_unidad'] === 'horas' ? 'horas' : 'minutos',
            'inactividad_activa' => (bool) $a['seg_inactividad_activa'],
            'inactividad_minutos' => self::entre((int) $a['seg_inactividad_minutos'], self::LIMITES['inactividad']),
        ];
    }

    /** Reglas que aplican a quien intenta entrar con ese correo (las de su negocio). */
    public static function reglasParaCorreo(string $email): array
    {
        $tenantId = User::query()->where('email', Str::lower(trim($email)))->value('tenant_id');

        return self::reglas($tenantId ? (int) $tenantId : null);
    }

    /** "15 minutos", "1 hora", "2 horas", "1 h 30 min" */
    public static function textoDuracion(int $minutos): string
    {
        if ($minutos < 60) {
            return $minutos . ($minutos === 1 ? ' minuto' : ' minutos');
        }
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return $m ? "{$h} h {$m} min" : $h . ($h === 1 ? ' hora' : ' horas');
    }

    /** Guarda un intento de acceso en el historial (si la tabla existe). */
    public static function registrar(string $resultado, string $email, ?Request $request = null, ?User $usuario = null): void
    {
        if (! Schema::hasTable('accesos_log')) {
            return;
        }
        $email = Str::lower(trim($email));
        $usuario = $usuario ?? User::query()->where('email', $email)->first();
        DB::table('accesos_log')->insert([
            'tenant_id' => $usuario?->tenant_id,
            'user_id' => $usuario?->id,
            'email' => Str::limit($email, 150, ''),
            'ip' => $request?->ip(),
            'navegador' => Str::limit((string) $request?->userAgent(), 250, ''),
            'resultado' => $resultado,
            'created_at' => now(),
        ]);
    }

    private static function entre(int $n, array $rango): int
    {
        return max($rango[0], min($rango[1], $n));
    }
}
