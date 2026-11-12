<?php

namespace App\Support;

use App\Models\ComprobanteElectronico;
use App\Models\SunatConfiguracion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Notas de credito (tipo 07) y anulaciones de boletas y facturas ya aceptadas por SUNAT, vía Nubefact.
 */
class NubefactNotas
{
    /** Motivos de nota de credito que maneja el panel (catalogo 09 de SUNAT). */
    public const CODIGOS = [
        '01' => 'Anulación de la operación',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '09' => 'Disminución en el valor',
    ];

    /** Anula un comprobante aceptado. La anulacion total se hace con la operacion de baja de Nubefact. */
    public static function anular(ComprobanteElectronico $original, SunatConfiguracion $config, string $motivo): ComprobanteElectronico
    {
        self::exigirAceptado($original, $config);

        $res = self::enviar($config, [
            'operacion' => 'generar_anulacion',
            'tipo_de_comprobante' => $original->tipo === '01' ? 1 : 2,
            'serie' => $original->serie,
            'numero' => $original->numero,
            'motivo' => $motivo,
            'codigo_unico' => '',
        ]);

        $anulado = $res['respondio'] && empty($res['datos']['errors'] ?? null);

        $original->update([
            'estado' => $anulado ? 'anulado' : $original->estado,
            'aceptado' => $anulado ? false : $original->aceptado,
            'mensaje' => $res['mensaje'] ?? $original->mensaje,
        ]);

        return $original->fresh();
    }

    /**
     * Emite una nota de credito sobre un comprobante aceptado.
     * Con codigo 01 o 06 la nota cubre todo el comprobante; con 07 o 09 usa $itemsDevueltos.
     *
     * @param  array<int, array{descripcion:string, cantidad:float|int, precio_unitario:float|int, codigo?:string}>|null  $itemsDevueltos
     */
    public static function notaCredito(ComprobanteElectronico $original, SunatConfiguracion $config, string $codigo, string $motivo, ?array $itemsDevueltos = null): ComprobanteElectronico
    {
        self::exigirAceptado($original, $config);

        if (! isset(self::CODIGOS[$codigo])) {
            throw ValidationException::withMessages(['codigo' => 'El motivo de la nota de crédito no es válido.']);
        }

        $itemsOriginales = $original->payload['items'] ?? [];
        $total = in_array($codigo, ['01', '06'], true) ? null : $itemsDevueltos;
        $items = $total ?? $itemsOriginales;

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Indica los productos que se devuelven.']);
        }

        $calculo = Nubefact::calcular($items);

        if ($calculo['total'] > (float) $original->total + 0.01) {
            throw ValidationException::withMessages(['items' => 'La nota de crédito no puede superar el total del comprobante.']);
        }

        $serie = $original->tipo === '01' ? $config->serie_factura : $config->serie_boleta;
        $cliente = [
            'documento' => $original->cliente_documento,
            'nombre' => $original->cliente_nombre,
            'direccion' => $original->cliente_direccion,
        ];

        $nota = DB::transaction(function () use ($config, $original, $serie, $calculo) {
            SunatConfiguracion::withoutGlobalScopes()->whereKey($config->id)->lockForUpdate()->first();

            $ultimo = ComprobanteElectronico::withoutGlobalScopes()
                ->where('tenant_id', $config->tenant_id)->where('tipo', '07')->where('serie', $serie)
                ->max('numero');

            return ComprobanteElectronico::withoutGlobalScopes()->create([
                'tenant_id' => $config->tenant_id,
                'pedido_id' => $original->pedido_id,
                'tipo' => '07',
                'serie' => $serie,
                'numero' => ((int) $ultimo) + 1,
                'cliente_tipo_documento' => $original->cliente_tipo_documento,
                'cliente_documento' => $original->cliente_documento,
                'cliente_nombre' => $original->cliente_nombre,
                'cliente_direccion' => $original->cliente_direccion,
                'subtotal' => $calculo['gravada'],
                'igv' => $calculo['igv'],
                'total' => $calculo['total'],
                'estado' => 'pendiente',
            ]);
        });

        $payload = Nubefact::payload($original->tipo, $serie, $nota->numero, $cliente, $calculo);
        $payload['tipo_de_comprobante'] = 3;
        $payload['codigo_nota_credito'] = $codigo;
        $payload['motivo'] = $motivo;
        $payload['documento_que_se_modifica_tipo'] = $original->tipo === '01' ? 1 : 2;
        $payload['documento_que_se_modifica_serie'] = $original->serie;
        $payload['documento_que_se_modifica_numero'] = $original->numero;

        $res = self::enviar($config, $payload);
        $aceptada = $res['respondio'] && ($res['datos']['aceptada_por_sunat'] ?? false) === true;

        $nota->update([
            'estado' => $aceptada ? 'aceptado' : ($res['respondio'] ? 'rechazado' : 'error'),
            'aceptado' => $aceptada,
            'mensaje' => $res['mensaje'],
            'enlace_pdf' => $res['datos']['enlace_del_pdf'] ?? null,
            'enlace_xml' => $res['datos']['enlace_del_xml'] ?? null,
            'enlace_cdr' => $res['datos']['enlace_del_cdr'] ?? null,
            'codigo_hash' => $res['datos']['codigo_hash'] ?? null,
            'payload' => $payload,
            'emitido_at' => now(),
        ]);

        return $nota->fresh();
    }

    private static function exigirAceptado(ComprobanteElectronico $c, SunatConfiguracion $config): void
    {
        if (! $config->activo || ! $config->ruta || $config->token === null) {
            throw ValidationException::withMessages(['config' => 'Nubefact no está activo o falta configurarlo.']);
        }

        if (! in_array($c->tipo, ['01', '03'], true) || $c->estado !== 'aceptado') {
            throw ValidationException::withMessages(['estado' => 'Solo puedes anular o emitir una nota de crédito sobre un comprobante aceptado por SUNAT.']);
        }
    }

    /** @return array{respondio: bool, datos: array, mensaje: ?string} */
    private static function enviar(SunatConfiguracion $config, array $payload): array
    {
        try {
            $respuesta = Http::withHeaders(['Authorization' => 'Token token="' . $config->tokenClaro() . '"'])
                ->acceptJson()->timeout(30)->post($config->ruta, $payload);
        } catch (\Throwable $e) {
            return ['respondio' => false, 'datos' => [], 'mensaje' => 'No se pudo conectar con Nubefact: ' . $e->getMessage()];
        }

        $datos = $respuesta->json() ?? [];
        $errores = $datos['errors'] ?? null;

        $mensaje = $datos['sunat_description'] ?? $errores ?? null;
        if (is_array($mensaje)) {
            $mensaje = json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        }
        if ($mensaje === null && ! $respuesta->successful()) {
            $mensaje = 'Respuesta HTTP ' . $respuesta->status();
        }

        return ['respondio' => $respuesta->successful(), 'datos' => $datos, 'mensaje' => $mensaje];
    }
}
