<?php

namespace App\Console\Commands;

use App\Support\BoletinInactivos;
use App\Support\Fidelizacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * GEST-78: cada semana escribe a los clientes inactivos de los negocios con boletín activo
 * (sin repetir al mismo cliente en 30 días). También vence los puntos sin movimiento (GEST-75).
 */
class EnviarBoletinInactivos extends Command
{
    protected $signature = 'campanas:boletin-inactivos';

    protected $description = 'Envía el boletín semanal a clientes inactivos y vence puntos sin movimiento';

    public function handle(): int
    {
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $vencidos = Fidelizacion::vencerSaldos((int) $tenantId);
            if ($vencidos) {
                $this->info("Negocio #{$tenantId}: vencieron los puntos de {$vencidos} clientes.");
            }
        }

        $negocios = DB::table('tenants')->where('boletin_activo', true)
            ->whereNotNull('boletin_texto')->where('boletin_texto', '!=', '')->pluck('id');

        foreach ($negocios as $tenantId) {
            [$registrados, $enviados] = BoletinInactivos::enviar((int) $tenantId);
            BoletinInactivos::marcarRegresos((int) $tenantId);
            $this->info("Negocio #{$tenantId}: {$registrados} clientes inactivos, {$enviados} mensajes enviados.");
        }

        return self::SUCCESS;
    }
}
