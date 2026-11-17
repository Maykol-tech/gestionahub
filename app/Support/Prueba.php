<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-91: prueba gratis de 14 días. Muestra cuántos días quedan, avisa a los 7, 3 y 1 días,
 * y cuando vence deja el panel en solo lectura (se puede ver todo, pero no guardar) hasta que pague.
 * Nunca se borra información.
 */
class Prueba
{
    public const DIAS = 14;
    public const AVISOS = [7, 3, 1];
    /** Días de gracia cuando un cobro recurrente falla antes de pasar a solo lectura. */
    public const GRACIA = 3;

    /** Rutas que siguen funcionando en solo lectura: pagar, cerrar sesión y desbloquear la pantalla. */
    public const RUTAS_PERMITIDAS = [
        'logout', 'admin.planes.*', 'admin.checkout.*', 'bloqueo.*', 'superadmin.*',
    ];

    private static array $cache = [];

    public static function de(?object $tenant): array
    {
        $vacio = ['en_prueba' => false, 'dias' => null, 'vence' => null, 'vencida' => false, 'solo_lectura' => false, 'motivo' => null];
        if (! $tenant) {
            return $vacio;
        }
        $clave = $tenant->id . '|' . ($tenant->estado ?? '') . '|' . ($tenant->trial_ends_at ?? '');
        if (isset(self::$cache[$clave])) {
            return self::$cache[$clave];
        }

        $r = $vacio;
        if (($tenant->estado ?? null) === 'trial' && ! empty($tenant->trial_ends_at)) {
            $fin = Carbon::parse($tenant->trial_ends_at);
            $r['en_prueba'] = true;
            $r['vence'] = $fin;
            $r['dias'] = max(0, (int) ceil(now()->diffInMinutes($fin, false) / 1440));
            $r['vencida'] = $fin->isPast();
            if ($r['vencida']) {
                $r['solo_lectura'] = true;
                $r['motivo'] = 'prueba';
            }
        } elseif (($tenant->estado ?? null) === 'activo' && Schema::hasTable('suscripciones_saas')) {
            // Suscripción con cobro automático que falló y ya pasó la gracia
            $sus = DB::table('suscripciones_saas')->where('tenant_id', $tenant->id)->orderByDesc('id')->first();
            if ($sus && $sus->estado === 'vencida' && $sus->vence_at && Carbon::parse($sus->vence_at)->addDays(self::GRACIA)->isPast()) {
                $r['vencida'] = true;
                $r['solo_lectura'] = true;
                $r['motivo'] = 'pago';
                $r['vence'] = Carbon::parse($sus->vence_at);
            }
        }

        return self::$cache[$clave] = $r;
    }

    public static function limpiarCache(): void
    {
        self::$cache = [];
    }

    /** Envía los avisos de 7, 3 y 1 días (una sola vez cada uno). Devuelve cuántos envió. */
    public static function enviarAvisos(): int
    {
        $enviados = 0;
        $tenants = DB::table('tenants')->where('estado', 'trial')->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())->where('trial_ends_at', '<=', now()->addDays(8))->get();

        foreach ($tenants as $t) {
            $dias = self::de($t)['dias'];
            $ya = json_decode((string) ($t->trial_avisos ?? '[]'), true) ?: [];
            // El aviso que toca es el más chico que ya alcanzó (ej. si quedan 2 días, el de 3)
            $toca = collect(self::AVISOS)->filter(fn ($a) => $dias <= $a)->sort()->first();
            if (! $toca || in_array($toca, $ya, true)) {
                continue;
            }

            $texto = "Hola, a tu prueba gratis de GestionaHub le " . ($dias === 1 ? 'queda 1 día' : "quedan {$dias} días")
                . ". Elige tu plan para seguir trabajando sin interrupciones (no perderás nada de tu información): "
                . url('/admin/planes');
            try {
                Mail::raw($texto, fn ($m) => $m->to($t->email)->subject($dias === 1 ? 'Tu prueba termina mañana' : "Te quedan {$dias} días de prueba"));
            } catch (\Throwable $e) {
                Log::warning('Aviso de prueba no enviado', ['tenant' => $t->id, 'error' => $e->getMessage()]);
            }

            $ya[] = $toca;
            DB::table('tenants')->where('id', $t->id)->update(['trial_avisos' => json_encode(array_values(array_unique($ya)))]);
            $enviados++;
        }

        return $enviados;
    }
}
