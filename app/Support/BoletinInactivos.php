<?php

namespace App\Support;

use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\VentaPos;
use App\Models\WhatsappConexion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * GEST-78: boletín a clientes inactivos.
 *  - Inactivo = sin pedidos ni ventas POS en X días (configurable).
 *  - No se le vuelve a escribir al mismo cliente en 30 días.
 *  - Se registra a quién se envió y quién volvió a comprar después.
 */
class BoletinInactivos
{
    public const SIN_REPETIR_DIAS = 30;

    public static function config(int $tenantId): array
    {
        $t = DB::table('tenants')->where('id', $tenantId)->first(['boletin_activo', 'boletin_texto']);
        $a = AjustesNegocio::leer($tenantId);

        return [
            'activo' => (bool) ($t->boletin_activo ?? false),
            'texto' => (string) ($t->boletin_texto ?? ''),
            'dias' => max(7, (int) $a['boletin_dias']),
            'cupon' => (string) $a['boletin_cupon'],
        ];
    }

    /** Clientes inactivos con teléfono a los que se puede escribir hoy. */
    public static function destinatarios(int $tenantId): Collection
    {
        $dias = self::config($tenantId)['dias'];
        $desde = now()->subDays($dias);

        $conPedido = Pedido::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNotNull('cliente_id')
            ->where('created_at', '>=', $desde)->select('cliente_id');
        $conVenta = VentaPos::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNotNull('cliente_id')
            ->where('created_at', '>=', $desde)->select('cliente_id');
        $yaAvisados = DB::table('boletin_envios')->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(self::SIN_REPETIR_DIAS))->select('cliente_id');

        return CrmCliente::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->where('activo', true)->whereNotNull('telefono')->where('telefono', '!=', '')
            ->where('created_at', '<', $desde)
            ->whereNotIn('id', $conPedido)->whereNotIn('id', $conVenta)->whereNotIn('id', $yaAvisados)
            ->orderBy('id')->limit(500)->get(['id', 'nombre', 'telefono']);
    }

    public static function mensaje(string $plantilla, string $nombre, string $cupon): string
    {
        $texto = str_replace(['{nombre}', '{cupon}'], [trim(explode(' ', $nombre)[0]), $cupon], $plantilla);
        if ($cupon !== '' && ! str_contains($plantilla, '{cupon}')) {
            $texto .= "\nUsa el cupón {$cupon} en tu próxima compra.";
        }

        return $texto;
    }

    /** Envía el boletín. Devuelve [registrados, enviados]. */
    public static function enviar(int $tenantId): array
    {
        $cfg = self::config($tenantId);
        if (trim($cfg['texto']) === '') {
            return [0, 0];
        }

        $conexion = WhatsappConexion::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)->first();
        $registrados = 0;
        $enviados = 0;

        foreach (self::destinatarios($tenantId) as $c) {
            $telefono = preg_replace('/\D/', '', (string) $c->telefono);
            $ok = false;
            if ($conexion) {
                try {
                    $ok = WhatsAppNegocio::enviarTexto($conexion, $telefono, self::mensaje($cfg['texto'], (string) $c->nombre, $cfg['cupon']), 'campana');
                } catch (\Throwable $e) {
                    $ok = false;
                }
            }
            DB::table('boletin_envios')->insert([
                'tenant_id' => $tenantId, 'cliente_id' => $c->id, 'telefono' => $telefono,
                'cupon' => $cfg['cupon'] ?: null, 'enviado' => $ok, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $registrados++;
            $ok && $enviados++;
        }

        return [$registrados, $enviados];
    }

    /** Marca a los clientes que volvieron a comprar después del boletín. */
    public static function marcarRegresos(int $tenantId): void
    {
        $pendientes = DB::table('boletin_envios')->where('tenant_id', $tenantId)->whereNull('volvio_at')
            ->where('created_at', '>=', now()->subDays(90))->get(['id', 'cliente_id', 'created_at']);

        foreach ($pendientes as $e) {
            $fechas = array_filter([
                Pedido::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('cliente_id', $e->cliente_id)->where('created_at', '>', $e->created_at)->min('created_at'),
                VentaPos::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('cliente_id', $e->cliente_id)->where('created_at', '>', $e->created_at)->min('created_at'),
            ]);
            if ($fechas) {
                DB::table('boletin_envios')->where('id', $e->id)->update(['volvio_at' => min($fechas), 'updated_at' => now()]);
            }
        }
    }

    public static function resumen(int $tenantId): array
    {
        self::marcarRegresos($tenantId);
        $base = DB::table('boletin_envios')->where('tenant_id', $tenantId)->where('created_at', '>=', now()->subDays(90));
        $total = (clone $base)->count();
        $volvieron = (clone $base)->whereNotNull('volvio_at')->count();

        $ultimos = DB::table('boletin_envios as b')
            ->leftJoin('crm_clientes as c', 'c.id', '=', 'b.cliente_id')
            ->where('b.tenant_id', $tenantId)->orderByDesc('b.id')->limit(15)
            ->get(['c.nombre', 'b.telefono', 'b.enviado', 'b.volvio_at', 'b.created_at'])
            ->map(fn ($r) => [
                'nombre' => $r->nombre ?? 'Cliente', 'telefono' => $r->telefono, 'enviado' => (bool) $r->enviado,
                'fecha' => \Carbon\Carbon::parse($r->created_at)->format('d/m/Y'),
                'volvio' => $r->volvio_at ? \Carbon\Carbon::parse($r->volvio_at)->format('d/m/Y') : null,
            ]);

        return [
            'pendientes' => self::destinatarios($tenantId)->count(),
            'enviados' => (clone $base)->where('enviado', true)->count(),
            'registrados' => $total,
            'volvieron' => $volvieron,
            'tasa' => $total ? round($volvieron / $total * 100, 1) : 0,
            'ultimos' => $ultimos,
        ];
    }
}
