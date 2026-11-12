<?php

namespace App\Support;

use App\Models\ComprobanteElectronico;
use App\Models\SunatConfiguracion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Emision de boletas y facturas electronicas mediante la API de Nubefact (SUNAT).
 * Los precios de los productos se consideran con IGV incluido (uso habitual en Peru).
 */
class Nubefact
{
    public const TASA_IGV = 0.18;

    /**
     * Calcula los importes del comprobante a partir de los items (precio con IGV incluido).
     * Los totales se suman por item para que coincidan con el detalle.
     *
     * @param  array<int, array{descripcion:string, cantidad:float|int, precio_unitario:float|int, codigo?:string}>  $items
     * @return array{items: array, gravada: float, igv: float, total: float}
     */
    public static function calcular(array $items): array
    {
        $detalle = [];
        $gravada = 0.0;
        $igv = 0.0;
        $total = 0.0;

        foreach (array_values($items) as $i => $it) {
            $cantidad = round((float) $it['cantidad'], 2);
            $precio = round((float) $it['precio_unitario'], 2);

            $totalItem = round($cantidad * $precio, 2);
            $valorItem = round($totalItem / (1 + self::TASA_IGV), 2);
            $igvItem = round($totalItem - $valorItem, 2);
            $valorUnitario = $cantidad > 0 ? round($valorItem / $cantidad, 2) : 0.0;

            $detalle[] = [
                'unidad_de_medida' => 'NIU',
                'codigo' => (string) ($it['codigo'] ?? 'ITEM-' . ($i + 1)),
                'descripcion' => (string) $it['descripcion'],
                'cantidad' => $cantidad,
                'valor_unitario' => $valorUnitario,
                'precio_unitario' => $precio,
                'descuento' => 0,
                'subtotal' => $valorItem,
                'tipo_de_igv' => 1,
                'igv' => $igvItem,
                'total' => $totalItem,
                'anticipo_regularizacion' => false,
            ];

            $gravada += $valorItem;
            $igv += $igvItem;
            $total += $totalItem;
        }

        return [
            'items' => $detalle,
            'gravada' => round($gravada, 2),
            'igv' => round($igv, 2),
            'total' => round($total, 2),
        ];
    }

    /** Reglas de SUNAT: la factura exige RUC y la boleta exige DNI desde S/ 700. */
    public static function validarCliente(string $tipo, ?string $documento, float $total): void
    {
        $doc = preg_replace('/\D/', '', (string) $documento);

        if ($tipo === '01' && strlen($doc) !== 11) {
            throw ValidationException::withMessages(['documento' => 'Para factura necesitas el RUC (11 dígitos).']);
        }

        if ($tipo === '03' && $total >= 700 && strlen($doc) !== 8) {
            throw ValidationException::withMessages(['documento' => 'Desde S/ 700 la boleta necesita el DNI (8 dígitos).']);
        }
    }

    /** Arma el JSON que espera la API de Nubefact para generar un comprobante. */
    public static function payload(string $tipo, string $serie, int $numero, array $cliente, array $calculo, ?string $fecha = null): array
    {
        $esFactura = $tipo === '01';
        $doc = preg_replace('/\D/', '', (string) ($cliente['documento'] ?? ''));

        if ($esFactura) {
            $tipoDoc = 6; // RUC
        } elseif ($doc !== '' && strlen($doc) === 8) {
            $tipoDoc = 1; // DNI
        } else {
            $tipoDoc = 0; // sin documento
            $doc = '';
        }

        return [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => $esFactura ? 1 : 2,
            'serie' => $serie,
            'numero' => $numero,
            'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => $tipoDoc,
            'cliente_numero_de_documento' => $doc,
            'cliente_denominacion' => (string) ($cliente['nombre'] ?? 'CLIENTES VARIOS'),
            'cliente_direccion' => (string) ($cliente['direccion'] ?? ''),
            'cliente_email' => (string) ($cliente['email'] ?? ''),
            'fecha_de_emision' => $fecha ?? Carbon::now('America/Lima')->format('d-m-Y'),
            'moneda' => 1,
            'porcentaje_de_igv' => self::TASA_IGV * 100,
            'descuento_global' => 0,
            'total_descuento' => 0,
            'total_anticipo' => 0,
            'total_gravada' => $calculo['gravada'],
            'total_inafecta' => 0,
            'total_exonerada' => 0,
            'total_gratuita' => 0,
            'total_igv' => $calculo['igv'],
            'total' => $calculo['total'],
            'forma_de_pago' => 'Contado',
            'items' => $calculo['items'],
        ];
    }

    /**
     * Reserva el siguiente numero, guarda el comprobante como pendiente, lo envia a Nubefact
     * y actualiza el resultado. El numero queda reservado aunque la llamada falle.
     */
    public static function emitir(SunatConfiguracion $config, string $tipo, array $cliente, array $items, ?int $pedidoId = null, ?int $ventaPosId = null): ComprobanteElectronico
    {
        self::exigirConfiguracion($config);

        $calculo = self::calcular($items);
        self::validarCliente($tipo, $cliente['documento'] ?? null, $calculo['total']);

        $serie = $tipo === '01' ? $config->serie_factura : $config->serie_boleta;

        $comprobante = DB::transaction(function () use ($config, $tipo, $serie, $cliente, $calculo, $pedidoId, $ventaPosId) {
            // Bloquea la configuracion del negocio para que dos emisiones no tomen el mismo numero
            SunatConfiguracion::withoutGlobalScopes()->whereKey($config->id)->lockForUpdate()->first();

            $ultimo = ComprobanteElectronico::withoutGlobalScopes()
                ->where('tenant_id', $config->tenant_id)->where('tipo', $tipo)->where('serie', $serie)
                ->max('numero');

            return ComprobanteElectronico::withoutGlobalScopes()->create([
                'tenant_id' => $config->tenant_id,
                'pedido_id' => $pedidoId,
                'venta_pos_id' => $ventaPosId,
                'tipo' => $tipo,
                'serie' => $serie,
                'numero' => ((int) $ultimo) + 1,
                'cliente_tipo_documento' => self::tipoDocumento($tipo, $cliente['documento'] ?? null),
                'cliente_documento' => preg_replace('/\D/', '', (string) ($cliente['documento'] ?? '')) ?: null,
                'cliente_nombre' => (string) ($cliente['nombre'] ?? 'CLIENTES VARIOS'),
                'cliente_direccion' => $cliente['direccion'] ?? null,
                'subtotal' => $calculo['gravada'],
                'igv' => $calculo['igv'],
                'total' => $calculo['total'],
                'estado' => 'pendiente',
                'simulado' => $config->esSimulado(),
            ]);
        });

        $payload = self::payload($tipo, $serie, $comprobante->numero, $cliente, $calculo);

        if ($config->esSimulado()) {
            return self::aceptarSimulado($comprobante, $payload);
        }

        try {
            $respuesta = Http::withHeaders(['Authorization' => 'Token token="' . $config->tokenClaro() . '"'])
                ->acceptJson()->timeout(30)->post($config->ruta, $payload);
            $datos = $respuesta->json() ?? [];
        } catch (\Throwable $e) {
            $comprobante->update([
                'estado' => 'error', 'payload' => $payload,
                'mensaje' => 'No se pudo conectar con Nubefact: ' . $e->getMessage(),
            ]);

            return $comprobante->fresh();
        }

        $aceptado = $respuesta->successful() && ($datos['aceptada_por_sunat'] ?? false) === true;

        $mensaje = $datos['sunat_description'] ?? $datos['errors'] ?? null;

        $comprobante->update([
            'estado' => $aceptado ? 'aceptado' : ($respuesta->successful() ? 'rechazado' : 'error'),
            'aceptado' => $aceptado,
            'payload' => $payload,
            'mensaje' => is_array($mensaje) ? json_encode($mensaje, JSON_UNESCAPED_UNICODE) : ($mensaje ?? ($respuesta->successful() ? null : 'Respuesta HTTP ' . $respuesta->status())),
            'enlace_pdf' => $datos['enlace_del_pdf'] ?? null,
            'enlace_xml' => $datos['enlace_del_xml'] ?? null,
            'enlace_cdr' => $datos['enlace_del_cdr'] ?? null,
            'codigo_hash' => $datos['codigo_hash'] ?? null,
            'emitido_at' => now(),
        ]);

        return $comprobante->fresh();
    }

    /** Revisa que el negocio pueda emitir: en simulado basta con estar activo; con Nubefact pide URL y token. */
    public static function exigirConfiguracion(SunatConfiguracion $config): void
    {
        if (! $config->activo) {
            throw ValidationException::withMessages(['config' => 'La emisión de comprobantes está desactivada. Actívala en Comprobantes.']);
        }

        if (! $config->esSimulado() && (! $config->ruta || $config->getRawOriginal('token') === null)) {
            throw ValidationException::withMessages(['config' => 'Configura la URL y el token de Nubefact antes de emitir comprobantes.']);
        }
    }

    /** Codigo SUNAT del documento del cliente: 6 RUC, 1 DNI, 0 sin documento. */
    public static function tipoDocumento(string $tipo, ?string $documento): string
    {
        $doc = preg_replace('/\D/', '', (string) $documento);

        return match (true) {
            $tipo === '01' || strlen($doc) === 11 => '6',
            strlen($doc) === 8 => '1',
            default => '0',
        };
    }

    /**
     * Modo simulado: el comprobante se da por aceptado sin llamar a SUNAT.
     * Sirve para demos y para que el negocio pruebe el flujo antes de contratar Nubefact.
     */
    public static function aceptarSimulado(ComprobanteElectronico $comprobante, array $payload): ComprobanteElectronico
    {
        $comprobante->update([
            'estado' => 'aceptado',
            'aceptado' => true,
            'simulado' => true,
            'payload' => $payload,
            'mensaje' => 'Simulado: comprobante válido solo para pruebas (no se envió a SUNAT).',
            'codigo_hash' => substr(base64_encode(hash('sha256', $comprobante->id . '|' . $comprobante->numero_completo . '|' . now()->timestamp, true)), 0, 28),
            'enlace_pdf' => route('admin.comprobantes.ver', $comprobante->id),
            'emitido_at' => now(),
        ]);

        return $comprobante->fresh();
    }
}
