<?php

namespace App\Console\Commands;

use App\Support\CarritosAbandonados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** GEST-85: envía el recordatorio de WhatsApp de los carritos abandonados (uno por carrito). */
class RecordarCarritosAbandonados extends Command
{
    protected $signature = 'carritos:recordar';

    protected $description = 'Envía por WhatsApp el enlace para recuperar los carritos abandonados';

    public function handle(): int
    {
        $tenants = DB::table('checkout_abandonados')->where('recuperado', false)->whereNull('recordado_at')
            ->distinct()->pluck('tenant_id');

        foreach ($tenants as $tenantId) {
            $n = CarritosAbandonados::recordar((int) $tenantId);
            if ($n) {
                $this->info("Negocio #{$tenantId}: {$n} recordatorios.");
            }
        }

        return self::SUCCESS;
    }
}
