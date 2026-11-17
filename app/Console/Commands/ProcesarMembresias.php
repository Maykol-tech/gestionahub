<?php

namespace App\Console\Commands;

use App\Support\Membresias;
use Illuminate\Console\Command;

/** GEST-57: crea los cobros de las suscripciones de clientes, marca vencidas y avisa por WhatsApp. */
class ProcesarMembresias extends Command
{
    protected $signature = 'membresias:procesar';

    protected $description = 'Cobros recurrentes de las suscripciones de clientes';

    public function handle(): int
    {
        [$creados, $vencidas] = Membresias::procesar();
        $this->info("Cobros creados: {$creados}. Suscripciones vencidas: {$vencidas}.");

        return self::SUCCESS;
    }
}
