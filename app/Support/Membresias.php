<?php

namespace App\Support;

use App\Models\CobroSuscripcion;
use App\Models\CrmCliente;
use App\Models\PlanCliente;
use App\Models\SuscripcionCliente;
use App\Models\WhatsappConexion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * GEST-57: suscripciones recurrentes de los clientes del negocio.
 *  - Al suscribirse se crea el primer cobro (por pagar). Al pagarlo, la suscripción queda activa.
 *  - Cada día se crea el cobro del siguiente periodo cuando llega la fecha y se avisa por WhatsApp.
 *  - Si un cobro pasa DIAS_GRACIA días sin pagarse, la suscripción queda vencida (y se avisa).
 *  - Al pagar un cobro vencido, la suscripción vuelve a estar activa.
 */
class Membresias
{
    public const DIAS_GRACIA = 3;

    public static function siguiente(Carbon $desde, string $periodo): Carbon
    {
        return match ($periodo) {
            'semanal' => $desde->copy()->addWeek(),
            'quincenal' => $desde->copy()->addDays(15),
            'anual' => $desde->copy()->addYearNoOverflow(),
            default => $desde->copy()->addMonthNoOverflow(),
        };
    }

    /** Equivalente mensual de un precio (para "ingresos recurrentes del mes"). */
    public static function mensual(float $precio, string $periodo): float
    {
        return round(match ($periodo) {
            'semanal' => $precio * 52 / 12,
            'quincenal' => $precio * 2,
            'anual' => $precio / 12,
            default => $precio,
        }, 2);
    }

    public static function suscribir(PlanCliente $plan, CrmCliente $cliente): SuscripcionCliente
    {
        if (! $plan->activo) {
            throw ValidationException::withMessages(['plan_id' => 'Ese plan ya no está disponible.']);
        }
        $ya = SuscripcionCliente::withoutGlobalScopes()->where('tenant_id', $plan->tenant_id)->where('cliente_id', $cliente->id)
            ->where('plan_id', $plan->id)->whereIn('estado', ['pendiente', 'activa', 'vencida'])->first();
        if ($ya) {
            return $ya;
        }

        return DB::transaction(function () use ($plan, $cliente) {
            $hoy = today();
            $s = SuscripcionCliente::withoutGlobalScopes()->create([
                'tenant_id' => $plan->tenant_id, 'plan_id' => $plan->id, 'cliente_id' => $cliente->id,
                'estado' => 'pendiente', 'precio' => $plan->precio, 'periodicidad' => $plan->periodicidad,
                'inicio' => $hoy, 'proximo_cobro' => $hoy, 'token' => Str::random(32),
            ]);
            self::crearCobro($s, $hoy);

            return $s;
        });
    }

    public static function crearCobro(SuscripcionCliente $s, Carbon $desde): CobroSuscripcion
    {
        $hasta = self::siguiente($desde, $s->periodicidad);

        return CobroSuscripcion::withoutGlobalScopes()->create([
            'tenant_id' => $s->tenant_id, 'suscripcion_id' => $s->id, 'monto' => $s->precio,
            'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(),
            'vence' => $desde->copy()->addDays(self::DIAS_GRACIA)->toDateString(), 'estado' => 'pendiente',
        ]);
    }

    /** Registra el pago de un cobro: la suscripción queda activa hasta el fin del periodo. */
    public static function pagar(CobroSuscripcion $cobro, string $metodo, ?string $referencia = null): void
    {
        if ($cobro->estado === 'pagado') {
            return;
        }
        DB::transaction(function () use ($cobro, $metodo, $referencia) {
            $cobro->update(['estado' => 'pagado', 'metodo' => $metodo, 'referencia' => $referencia ?: $cobro->referencia, 'pagado_at' => now()]);
            $s = $cobro->suscripcion;
            if ($s && $s->estado !== 'cancelada') {
                $s->update(['estado' => 'activa', 'proximo_cobro' => $cobro->hasta]);
            }
        });
    }

    public static function cancelar(SuscripcionCliente $s): void
    {
        DB::transaction(function () use ($s) {
            $s->update(['estado' => 'cancelada', 'cancelada_at' => now()]);
            $s->cobros()->whereIn('estado', ['pendiente', 'por_revisar', 'vencido'])->update(['estado' => 'anulado']);
        });
    }

    public static function reactivar(SuscripcionCliente $s): void
    {
        if ($s->estado !== 'cancelada') {
            return;
        }
        DB::transaction(function () use ($s) {
            $s->update(['estado' => 'pendiente', 'cancelada_at' => null, 'proximo_cobro' => today()]);
            self::crearCobro($s, today());
        });
    }

    /** Tarea diaria: crea los cobros que tocan, marca vencidas y avisa por WhatsApp. Devuelve [creados, vencidas]. */
    public static function procesar(?Carbon $hoy = null): array
    {
        $hoy = ($hoy ?? today())->copy()->startOfDay();
        $creados = 0;
        $vencidas = 0;

        // 1) Nuevos periodos para las activas cuya fecha de cobro llegó.
        SuscripcionCliente::withoutGlobalScopes()->where('estado', 'activa')->whereDate('proximo_cobro', '<=', $hoy)->get()
            ->each(function (SuscripcionCliente $s) use (&$creados) {
                $abierto = $s->cobros()->whereIn('estado', ['pendiente', 'por_revisar'])->exists();
                if (! $abierto) {
                    $cobro = self::crearCobro($s, $s->proximo_cobro->copy());
                    $creados++;
                    self::avisar($s, $cobro, 'nuevo');
                }
            });

        // 2) Cobros sin pagar pasada la gracia: la suscripción queda vencida.
        CobroSuscripcion::withoutGlobalScopes()->where('estado', 'pendiente')->whereDate('vence', '<', $hoy)->get()
            ->each(function (CobroSuscripcion $c) use (&$vencidas) {
                $c->update(['estado' => 'vencido']);
                $s = $c->suscripcion;
                if ($s && in_array($s->estado, ['activa', 'pendiente'], true)) {
                    $s->update(['estado' => 'vencida']);
                    $vencidas++;
                    self::avisar($s, $c, 'vencido');
                }
            });

        return [$creados, $vencidas];
    }

    public static function enlace(SuscripcionCliente $s): string
    {
        return route('membresia.publica', $s->token);
    }

    public static function texto(SuscripcionCliente $s, CobroSuscripcion $c, string $tipo): string
    {
        $nombre = Str::of((string) optional($s->cliente)->nombre)->explode(' ')->first() ?: 'Hola';
        $plan = optional($s->plan)->nombre ?? 'tu plan';
        $monto = 'S/ ' . number_format($c->monto, 2);

        return $tipo === 'vencido'
            ? "Hola {$nombre}, tu suscripción \"{$plan}\" está vencida porque no registramos el pago de {$monto}. Puedes pagar aquí y seguir disfrutándola: " . self::enlace($s)
            : "Hola {$nombre}, ya puedes renovar \"{$plan}\" ({$monto}). Paga antes del " . $c->vence->format('d/m') . ' aquí: ' . self::enlace($s);
    }

    private static function avisar(SuscripcionCliente $s, CobroSuscripcion $c, string $tipo): void
    {
        $telefono = optional($s->cliente)->telefono;
        if (! $telefono) {
            return;
        }
        $conexion = WhatsappConexion::withoutGlobalScopes()->where('tenant_id', $s->tenant_id)->where('activo', true)->first();
        if (! $conexion) {
            return; // sin WhatsApp conectado, el negocio avisa con el botón del panel
        }
        try {
            if (WhatsAppNegocio::enviarTexto($conexion, $telefono, self::texto($s, $c, $tipo), 'recordatorio')) {
                $c->update(['avisado_at' => now()]);
            }
        } catch (\Throwable) {
            // un aviso fallido no detiene la tarea
        }
    }
}
