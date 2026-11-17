<?php

namespace App\Support;

use App\Models\LandingContenido;
use App\Models\LandingLead;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * GEST-93..96: checkout de la suscripción SaaS.
 * - Tarjeta con Niubiz (se activa al instante y puede renovarse sola).
 * - Yape, Plin o transferencia: el negocio sube la captura y el número de operación; el superadmin lo aprueba.
 * - Si abre el checkout y no paga, a los 30 minutos se vuelve un lead para darle seguimiento.
 * Usa las tablas suscripciones_saas y pagos_saas (no se borran).
 */
class Suscripciones
{
    public const METODOS_MANUALES = ['yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia bancaria'];
    public const MINUTOS_ABANDONO = 30;
    public const MAX_INTENTOS = 3;

    public const COBROS_DEFECTO = ['yape' => '', 'plin' => '', 'titular' => 'GestionaHub', 'banco' => '', 'cuenta' => '', 'cci' => '', 'qr_url' => ''];

    public static function cobros(): array
    {
        return array_replace(self::COBROS_DEFECTO, (array) (LandingContenido::todo()['cobros'] ?? []));
    }

    public static function plan(string $slug): ?object
    {
        return DB::table('planes_saas')->where('slug', $slug)->where('activo', true)->first();
    }

    public static function precio(object $plan, string $ciclo): float
    {
        if ($ciclo === 'anual') {
            return (float) $plan->precio_anual > 0 ? (float) $plan->precio_anual : round((float) $plan->precio_mensual * 10, 2);
        }

        return (float) $plan->precio_mensual;
    }

    /** Registra que el negocio abrió el checkout (reutiliza el de las últimas 2 horas). */
    public static function iniciarCheckout(int $tenantId, object $plan, string $ciclo): int
    {
        $abierto = DB::table('checkouts_saas')->where('tenant_id', $tenantId)->where('plan_id', $plan->id)
            ->where('ciclo', $ciclo)->where('estado', 'iniciado')->where('created_at', '>=', now()->subHours(2))->value('id');
        if ($abierto) {
            return (int) $abierto;
        }

        return (int) DB::table('checkouts_saas')->insertGetId([
            'tenant_id' => $tenantId, 'plan_id' => $plan->id, 'ciclo' => $ciclo, 'estado' => 'iniciado',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public static function normalizarOperacion(string $numero): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $numero));
    }

    /** GEST-95: el negocio reporta un pago manual con su captura. */
    public static function reportarManual(int $tenantId, object $plan, string $ciclo, string $metodo, string $numero, UploadedFile $captura, ?int $checkoutId = null): int
    {
        $numero = self::normalizarOperacion($numero);
        if (strlen($numero) < 4) {
            throw ValidationException::withMessages(['numero_operacion' => 'Escribe el número de operación completo que aparece en tu comprobante.']);
        }
        if (DB::table('pagos_saas')->where('metodo', $metodo)->where('numero_operacion', $numero)->exists()) {
            throw ValidationException::withMessages(['numero_operacion' => 'Ese número de operación ya fue usado en otro pago. Revisa tu comprobante.']);
        }
        if (DB::table('pagos_saas')->where('tenant_id', $tenantId)->where('estado', 'pendiente')->whereIn('metodo', array_keys(self::METODOS_MANUALES))->exists()) {
            throw ValidationException::withMessages(['numero_operacion' => 'Ya tienes un pago en revisión. Te avisaremos apenas lo confirmemos.']);
        }

        $ruta = $captura->store('comprobantes-saas/' . $tenantId, 'local');

        $pagoId = (int) DB::table('pagos_saas')->insertGetId([
            'tenant_id' => $tenantId, 'plan_id' => $plan->id, 'ciclo' => $ciclo, 'monto' => self::precio($plan, $ciclo),
            'pasarela' => 'manual', 'metodo' => $metodo, 'numero_operacion' => $numero, 'comprobante_path' => $ruta,
            'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($checkoutId) {
            DB::table('checkouts_saas')->where('id', $checkoutId)->where('tenant_id', $tenantId)
                ->update(['estado' => 'pagado', 'metodo' => $metodo, 'pago_id' => $pagoId, 'updated_at' => now()]);
        }

        return $pagoId;
    }

    /** El superadmin aprueba un pago manual: el plan queda activo. */
    public static function aprobar(int $pagoId, ?int $revisor = null): void
    {
        DB::transaction(function () use ($pagoId, $revisor) {
            $pago = DB::table('pagos_saas')->where('id', $pagoId)->lockForUpdate()->first();
            if (! $pago || $pago->estado !== 'pendiente') {
                throw ValidationException::withMessages(['pago' => 'Este pago ya fue revisado.']);
            }
            $susId = self::activar((int) $pago->tenant_id, (int) $pago->plan_id, $pago->ciclo ?: 'mensual', (float) $pago->monto, 'manual');
            DB::table('pagos_saas')->where('id', $pagoId)->update([
                'estado' => 'pagado', 'fecha_pago' => now(), 'suscripcion_id' => $susId,
                'revisado_por' => $revisor, 'revisado_at' => now(), 'updated_at' => now(),
            ]);
        });
        self::avisar((int) DB::table('pagos_saas')->where('id', $pagoId)->value('tenant_id'), 'Tu pago fue confirmado', 'Confirmamos tu pago. Tu plan de GestionaHub ya está activo. ¡Gracias por confiar en nosotros!');
    }

    public static function rechazar(int $pagoId, string $motivo, ?int $revisor = null): void
    {
        $pago = DB::table('pagos_saas')->where('id', $pagoId)->first();
        if (! $pago || $pago->estado !== 'pendiente') {
            throw ValidationException::withMessages(['pago' => 'Este pago ya fue revisado.']);
        }
        DB::table('pagos_saas')->where('id', $pagoId)->update([
            'estado' => 'fallido', 'motivo_rechazo' => $motivo, 'revisado_por' => $revisor, 'revisado_at' => now(), 'updated_at' => now(),
        ]);
        self::avisar((int) $pago->tenant_id, 'No pudimos confirmar tu pago', "No pudimos confirmar tu pago: {$motivo}. Puedes volver a reportarlo desde Planes y facturación.");
    }

    /**
     * Activa o renueva el plan. Si aún tenía días pagados, se suman desde esa fecha.
     * Devuelve el id de la suscripción.
     */
    public static function activar(int $tenantId, int $planId, string $ciclo, float $monto, string $pasarela, ?array $tarjeta = null): int
    {
        $actual = DB::table('suscripciones_saas')->where('tenant_id', $tenantId)->whereIn('estado', ['activa', 'vencida'])->orderByDesc('id')->first();
        $base = $actual && $actual->vence_at && Carbon::parse($actual->vence_at)->isFuture() ? Carbon::parse($actual->vence_at) : now();
        $vence = $ciclo === 'anual' ? $base->copy()->addYear() : $base->copy()->addMonth();

        $datos = [
            'plan_id' => $planId, 'ciclo' => $ciclo, 'monto' => $monto, 'pasarela' => $pasarela, 'estado' => 'activa',
            'vence_at' => $vence, 'intentos_fallidos' => 0, 'proximo_intento_at' => null, 'updated_at' => now(),
        ];
        if ($tarjeta) {
            $datos += ['tarjeta_token' => Crypt::encryptString($tarjeta['token']), 'tarjeta_marca' => $tarjeta['marca'] ?? null,
                'tarjeta_ultimos' => $tarjeta['ultimos'] ?? null, 'renovacion_auto' => true];
        }

        if ($actual) {
            DB::table('suscripciones_saas')->where('id', $actual->id)->update($datos);
            $susId = (int) $actual->id;
        } else {
            $susId = (int) DB::table('suscripciones_saas')->insertGetId($datos + ['tenant_id' => $tenantId, 'inicio_at' => now(), 'created_at' => now()]);
        }

        DB::table('tenants')->where('id', $tenantId)->update([
            'estado' => 'activo', 'plan_id' => $planId, 'suscripcion_hasta' => $vence, 'updated_at' => now(),
        ]);
        Prueba::limpiarCache();

        // El lead de este negocio pasa a "Ganado"
        LandingLead::query()->where('tenant_id', $tenantId)->whereNotIn('estado', ['ganado'])->update(['estado' => 'ganado', 'updated_at' => now()]);

        return $susId;
    }

    /** GEST-94: renueva con la tarjeta guardada las suscripciones que vencen. Devuelve [cobradas, fallidas]. */
    public static function cobrarRenovaciones(): array
    {
        $cobradas = 0;
        $fallidas = 0;
        $vencen = DB::table('suscripciones_saas')->where('estado', 'activa')->where('renovacion_auto', true)
            ->whereNotNull('tarjeta_token')->where('vence_at', '<=', now()->addHours(12))
            ->where(fn ($q) => $q->whereNull('proximo_intento_at')->orWhere('proximo_intento_at', '<=', now()))->get();

        foreach ($vencen as $s) {
            $plan = DB::table('planes_saas')->where('id', $s->plan_id)->first();
            $monto = $plan ? self::precio($plan, $s->ciclo) : (float) $s->monto;
            $intento = (int) $s->intentos_fallidos + 1;
            $compra = substr($s->id . now()->format('ymdHi') . $intento, 0, 12);

            try {
                [$ok, $res] = Niubiz::configurado()
                    ? Niubiz::cobrarTarjeta(Crypt::decryptString($s->tarjeta_token), $monto, $compra)
                    : [false, ['mensaje' => 'Niubiz no está configurado']];
            } catch (\Throwable $e) {
                [$ok, $res] = [false, ['mensaje' => $e->getMessage()]];
            }

            $pagoId = DB::table('pagos_saas')->insertGetId([
                'tenant_id' => $s->tenant_id, 'suscripcion_id' => $s->id, 'plan_id' => $s->plan_id, 'ciclo' => $s->ciclo,
                'monto' => $monto, 'pasarela' => 'niubiz', 'metodo' => 'tarjeta', 'pasarela_pago_id' => $res['id'] ?? null,
                'estado' => $ok ? 'pagado' : 'fallido', 'fecha_pago' => $ok ? now() : null, 'intento' => $intento,
                'motivo_rechazo' => $ok ? null : mb_substr((string) ($res['mensaje'] ?? ''), 0, 250),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            if ($ok) {
                self::activar((int) $s->tenant_id, (int) $s->plan_id, $s->ciclo, $monto, 'niubiz');
                $cobradas++;
                continue;
            }

            $fallidas++;
            if ($intento >= self::MAX_INTENTOS) {
                DB::table('suscripciones_saas')->where('id', $s->id)->update(['estado' => 'vencida', 'intentos_fallidos' => $intento, 'updated_at' => now()]);
                self::avisar((int) $s->tenant_id, 'No pudimos renovar tu plan', 'Intentamos cobrar tu plan ' . self::MAX_INTENTOS . ' veces y el banco lo rechazó. Actualiza tu tarjeta o paga con Yape, Plin o transferencia en ' . url('/admin/planes') . ' para no quedar en solo lectura.');
            } else {
                DB::table('suscripciones_saas')->where('id', $s->id)->update([
                    'intentos_fallidos' => $intento, 'proximo_intento_at' => now()->addDay(), 'updated_at' => now(),
                ]);
                self::avisar((int) $s->tenant_id, 'Tu pago no pasó', 'No pudimos cobrar tu plan (' . ($res['mensaje'] ?? 'rechazado') . '). Lo intentaremos de nuevo mañana.');
            }
            Log::info('Renovación Niubiz fallida', ['suscripcion' => $s->id, 'pago' => $pagoId, 'intento' => $intento]);
        }

        return [$cobradas, $fallidas];
    }

    /** GEST-96: checkouts sin pagar por más de 30 minutos se vuelven leads (una sola vez). */
    public static function marcarAbandonados(): int
    {
        $n = 0;
        $lista = DB::table('checkouts_saas')->where('estado', 'iniciado')->where('created_at', '<=', now()->subMinutes(self::MINUTOS_ABANDONO))->get();
        foreach ($lista as $c) {
            // Si pagó por otra vía mientras tanto, no es abandono
            $pago = DB::table('pagos_saas')->where('tenant_id', $c->tenant_id)->where('created_at', '>=', $c->created_at)->whereIn('estado', ['pendiente', 'pagado'])->exists();
            if ($pago) {
                DB::table('checkouts_saas')->where('id', $c->id)->update(['estado' => 'pagado', 'updated_at' => now()]);
                continue;
            }
            $tenant = DB::table('tenants')->where('id', $c->tenant_id)->first();
            $plan = DB::table('planes_saas')->where('id', $c->plan_id)->first();
            $usuario = DB::table('users')->where('tenant_id', $c->tenant_id)->orderBy('id')->first();
            if (! $tenant) {
                continue;
            }
            $lead = LandingLead::registrar([
                'nombre' => $usuario->name ?? $tenant->nombre_negocio,
                'email' => $usuario->email ?? $tenant->email,
                'telefono' => $tenant->telefono ?? null,
                'fuente' => 'checkout',
                'mensaje' => "{$tenant->nombre_negocio} abrió el pago del plan " . ($plan->nombre ?? '') . " ({$c->ciclo}) y no lo terminó.",
                'tenant_id' => $tenant->id,
            ]);
            DB::table('checkouts_saas')->where('id', $c->id)->update(['estado' => 'abandonado', 'lead_id' => $lead->id, 'updated_at' => now()]);
            self::avisar((int) $tenant->id, '¿Te ayudamos a activar tu plan?', 'Vimos que empezaste a activar tu plan y no terminaste. Si tuviste algún problema, respóndenos y te ayudamos: ' . url('/admin/planes'));
            $n++;
        }

        return $n;
    }

    private static function avisar(int $tenantId, string $asunto, string $texto): void
    {
        $email = DB::table('tenants')->where('id', $tenantId)->value('email');
        if (! $email) {
            return;
        }
        try {
            Mail::raw($texto, fn ($m) => $m->to($email)->subject($asunto));
        } catch (\Throwable $e) {
            Log::warning('Aviso de suscripción no enviado', ['tenant' => $tenantId, 'error' => $e->getMessage()]);
        }
    }
}
