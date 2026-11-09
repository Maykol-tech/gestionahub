<?php

namespace App\Support;

use App\Models\CampanaWhatsapp;
use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\WhatsappConexion;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Arma los segmentos de clientes y envia campanas desde el numero de WhatsApp del negocio. */
class Campanas
{
    /** Telefonos unicos de los clientes del segmento. */
    public static function destinatarios(int $tenantId, string $segmento): Collection
    {
        $pedidos = Pedido::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNotNull('cliente_id');

        $query = CrmCliente::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->where('activo', true)->whereNotNull('telefono')->where('telefono', '!=', '');

        if ($segmento === 'frecuentes') {
            $query->whereIn('id', (clone $pedidos)->select('cliente_id')->groupBy('cliente_id')->havingRaw('COUNT(*) >= 3'));
        }

        if ($segmento === 'inactivos') {
            // whereNotIn con NULL no devuelve filas, por eso se filtra cliente_id nulo
            $recientes = (clone $pedidos)->where('created_at', '>=', now()->subDays(60))->select('cliente_id');
            $query->whereNotIn('id', $recientes);
        }

        return $query->pluck('telefono')->map(fn ($t) => preg_replace('/\D/', '', (string) $t))
            ->filter()->unique()->values();
    }

    /** Envia la campana a todos los destinatarios y guarda el resultado. */
    public static function enviar(CampanaWhatsapp $campana): CampanaWhatsapp
    {
        if (! in_array($campana->estado, ['borrador', 'programada'], true)) {
            throw ValidationException::withMessages(['estado' => 'Esta campaña ya fue enviada o cancelada.']);
        }

        $conexion = WhatsappConexion::withoutGlobalScopes()->where('tenant_id', $campana->tenant_id)
            ->where('activo', true)->first();

        if (! $conexion) {
            throw ValidationException::withMessages(['conexion' => 'Conecta tu número de WhatsApp antes de enviar campañas.']);
        }

        if (! $campana->plantilla && ! $campana->texto) {
            throw ValidationException::withMessages(['texto' => 'La campaña necesita una plantilla o un texto.']);
        }

        $enviados = 0;
        $fallidos = 0;

        foreach (self::destinatarios((int) $campana->tenant_id, $campana->segmento) as $telefono) {
            $ok = $campana->plantilla
                ? WhatsAppNegocio::enviarPlantilla($conexion, $telefono, $campana->plantilla, 'campana')
                : WhatsAppNegocio::enviarTexto($conexion, $telefono, (string) $campana->texto, 'campana');

            $ok ? $enviados++ : $fallidos++;
        }

        $campana->update([
            'estado' => 'enviada',
            'enviados' => $enviados,
            'fallidos' => $fallidos,
            'enviada_at' => now(),
        ]);

        return $campana->fresh();
    }
}
