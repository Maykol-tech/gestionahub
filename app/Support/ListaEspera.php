<?php

namespace App\Support;

use App\Models\Cita;
use App\Models\ListaEsperaCita;
use App\Models\WhatsappConexion;
use Illuminate\Support\Facades\DB;

/**
 * GEST-86: cuando se cancela una cita, se avisa a la primera persona en espera para ese servicio
 * y fecha con un enlace para tomar el cupo. Si no lo toma en X minutos, pasa a la siguiente.
 */
class ListaEspera
{
    public static function minutos(int $tenantId): int
    {
        return max(5, (int) AjustesNegocio::leer($tenantId)['espera_minutos']);
    }

    /** ¿El horario de la cita cancelada sigue libre? */
    public static function cupoLibre(Cita $cancelada): bool
    {
        return ! Cita::withoutGlobalScopes()->where('tenant_id', $cancelada->tenant_id)
            ->where('id', '!=', $cancelada->id)->where('estado', '!=', 'cancelada')
            ->where('inicio', $cancelada->inicio)
            ->when($cancelada->servicio_id, fn ($q) => $q->where('servicio_id', $cancelada->servicio_id))
            ->exists();
    }

    /** Revisa citas canceladas recientes: vence ofertas sin respuesta y avisa a la siguiente persona. */
    public static function procesar(): int
    {
        $avisos = 0;

        // 1) Ofertas vencidas: la persona vuelve a esperar y el cupo pasa a la siguiente
        $vencidas = DB::table('lista_espera_ofertas')->where('estado', 'enviada')->where('expira_at', '<', now())->get();
        foreach ($vencidas as $o) {
            DB::table('lista_espera_ofertas')->where('id', $o->id)->update(['estado' => 'vencida', 'updated_at' => now()]);
            ListaEsperaCita::withoutGlobalScopes()->whereKey($o->espera_id)->where('estado', 'avisado')->update(['estado' => 'esperando']);
        }

        // 2) Citas canceladas en los últimos 2 días que aún no pasaron
        $canceladas = Cita::withoutGlobalScopes()->where('estado', 'cancelada')
            ->where('updated_at', '>=', now()->subDays(2))->where('inicio', '>', now())->get();

        foreach ($canceladas as $cita) {
            $ofertas = DB::table('lista_espera_ofertas')->where('cita_id', $cita->id);
            if ((clone $ofertas)->whereIn('estado', ['enviada', 'tomada', 'cerrada'])->exists()) {
                continue; // hay una oferta abierta o el cupo ya se tomó
            }
            if (! self::cupoLibre($cita)) {
                continue;
            }

            $yaOfrecidos = (clone $ofertas)->pluck('espera_id');
            $espera = ListaEsperaCita::withoutGlobalScopes()->where('tenant_id', $cita->tenant_id)
                ->where('estado', 'esperando')
                ->whereDate('fecha_deseada', $cita->inicio->toDateString())
                ->where(fn ($q) => $q->whereNull('servicio_id')->orWhere('servicio_id', $cita->servicio_id))
                ->whereNotIn('id', $yaOfrecidos)
                ->orderBy('created_at')->first();

            if (! $espera) {
                continue;
            }

            self::ofrecer($cita, $espera);
            $avisos++;
        }

        return $avisos;
    }

    public static function ofrecer(Cita $cita, ListaEsperaCita $espera): string
    {
        $minutos = self::minutos((int) $cita->tenant_id);
        $token = bin2hex(random_bytes(16));
        $enlace = url('/espera/' . $token);

        $ok = false;
        $conexion = WhatsappConexion::withoutGlobalScopes()->where('tenant_id', $cita->tenant_id)->where('activo', true)->first();
        if ($conexion) {
            $texto = "Hola {$espera->cliente_nombre}, se liberó un horario el {$cita->inicio->format('d/m')} a las {$cita->inicio->format('H:i')}"
                . ($cita->servicio ? " para {$cita->servicio->nombre}" : '') . ". Tómalo aquí antes de {$minutos} minutos: {$enlace}";
            try {
                $ok = WhatsAppNegocio::enviarTexto($conexion, preg_replace('/\D/', '', (string) $espera->cliente_telefono), $texto, 'lista_espera');
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        DB::table('lista_espera_ofertas')->insert([
            'tenant_id' => $cita->tenant_id, 'espera_id' => $espera->id, 'cita_id' => $cita->id, 'token' => $token,
            'estado' => 'enviada', 'whatsapp_ok' => $ok, 'expira_at' => now()->addMinutes($minutos),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $espera->update(['estado' => 'avisado', 'cita_origen_id' => $cita->id, 'avisado_at' => now()]);

        return $token;
    }

    /** La persona toma el cupo desde el enlace: se crea su cita. */
    public static function tomar(object $oferta): Cita
    {
        return DB::transaction(function () use ($oferta) {
            $o = DB::table('lista_espera_ofertas')->where('id', $oferta->id)->lockForUpdate()->first();
            $cancelada = Cita::withoutGlobalScopes()->findOrFail($o->cita_id);
            $espera = ListaEsperaCita::withoutGlobalScopes()->findOrFail($o->espera_id);

            if ($o->estado !== 'enviada' || now()->greaterThan($o->expira_at) || ! self::cupoLibre($cancelada)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['cupo' => 'Este cupo ya no está disponible.']);
            }

            $nueva = Cita::withoutGlobalScopes()->create([
                'tenant_id' => $cancelada->tenant_id, 'servicio_id' => $cancelada->servicio_id,
                'cliente_nombre' => $espera->cliente_nombre, 'cliente_telefono' => $espera->cliente_telefono,
                'inicio' => $cancelada->inicio, 'duracion_min' => $cancelada->duracion_min, 'precio' => $cancelada->precio,
                'estado' => 'confirmada', 'origen' => 'lista_espera', 'notas' => 'Tomó el cupo desde la lista de espera',
            ]);
            DB::table('lista_espera_ofertas')->where('id', $o->id)->update(['estado' => 'tomada', 'cita_nueva_id' => $nueva->id, 'updated_at' => now()]);
            $espera->update(['estado' => 'reservado']);

            return $nueva;
        });
    }
}
