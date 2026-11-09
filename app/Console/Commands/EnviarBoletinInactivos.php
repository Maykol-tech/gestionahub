<?php

namespace App\Console\Commands;

use App\Models\CampanaWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Crea, una vez al mes, la campaña de boletín para clientes inactivos de cada negocio que lo tenga activo.
 * La campaña queda programada y la envía campanas:enviar-programadas.
 */
class EnviarBoletinInactivos extends Command
{
    protected $signature = 'campanas:boletin-inactivos';

    protected $description = 'Programa el boletín mensual para clientes inactivos (negocios con boletín activo)';

    public function handle(): int
    {
        $nombre = 'Boletín automático ' . now()->format('Y-m');

        $negocios = DB::table('tenants')->where('boletin_activo', true)
            ->whereNotNull('boletin_texto')->where('boletin_texto', '!=', '')->get(['id', 'boletin_texto']);

        foreach ($negocios as $negocio) {
            $yaCreada = CampanaWhatsapp::withoutGlobalScopes()
                ->where('tenant_id', $negocio->id)->where('nombre', $nombre)->exists();

            if ($yaCreada) {
                continue;
            }

            CampanaWhatsapp::withoutGlobalScopes()->create([
                'tenant_id' => $negocio->id,
                'nombre' => $nombre,
                'segmento' => 'inactivos',
                'texto' => $negocio->boletin_texto,
                'estado' => 'programada',
                'programada_para' => now(),
            ]);

            $this->info("Boletín programado para el negocio #{$negocio->id}.");
        }

        return self::SUCCESS;
    }
}
