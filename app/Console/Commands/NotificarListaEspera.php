<?php

namespace App\Console\Commands;

use App\Support\ListaEspera;
use Illuminate\Console\Command;

/**
 * GEST-86: cuando se cancela una cita avisa a la primera persona en espera con un enlace para tomar el cupo.
 * Si no lo toma a tiempo, pasa a la siguiente. Corre cada 5 minutos.
 */
class NotificarListaEspera extends Command
{
    protected $signature = 'citas:lista-espera';

    protected $description = 'Avisa por WhatsApp a la lista de espera cuando se libera un horario';

    public function handle(): int
    {
        $n = ListaEspera::procesar();
        $this->info("Avisos enviados: {$n}.");

        return self::SUCCESS;
    }
}
