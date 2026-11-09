<?php

namespace App\Console\Commands;

use App\Models\Insumo;
use App\Models\WhatsappConexion;
use App\Support\WhatsAppNegocio;
use Illuminate\Console\Command;

/** Envia por WhatsApp el aviso de insumos bajo el stock minimo (GEST-71). Se programa una vez al dia. */
class AlertaStockInsumos extends Command
{
    protected $signature = 'insumos:alerta-stock';

    protected $description = 'Avisa por WhatsApp los insumos bajo el stock minimo';

    public function handle(): int
    {
        // Numero de prueba por defecto; se cambia con STOCK_ALERTA_TELEFONO en el .env
        $telefono = (string) env('STOCK_ALERTA_TELEFONO', '51999999999');
        $enviados = 0;

        WhatsappConexion::withoutGlobalScopes()->where('activo', true)->get()->each(function (WhatsappConexion $conexion) use ($telefono, &$enviados) {
            $insumos = Insumo::withoutGlobalScopes()->where('tenant_id', $conexion->tenant_id)
                ->where('activo', true)->whereColumn('stock', '<=', 'stock_minimo')
                ->orderBy('nombre')->get(['nombre', 'stock', 'stock_minimo', 'unidad']);

            if ($insumos->isEmpty()) {
                return;
            }

            $lineas = $insumos->map(fn ($i) => "- {$i->nombre}: {$i->stock} {$i->unidad} (mínimo {$i->stock_minimo})")->implode("\n");
            $texto = "Alerta de stock bajo ({$insumos->count()}):\n{$lineas}";

            if (WhatsAppNegocio::enviarTexto($conexion, $telefono, $texto, 'alerta')) {
                $enviados++;
            }
        });

        $this->info("Avisos enviados: {$enviados}");

        return self::SUCCESS;
    }
}
