<?php

namespace App\Console\Commands;

use App\Support\Suscripciones;
use Illuminate\Console\Command;

/** GEST-96: los checkouts de suscripción sin pagar por más de 30 minutos pasan a la bandeja de leads. */
class CheckoutsAbandonados extends Command
{
    protected $signature = 'checkouts:abandonados';

    protected $description = 'Convierte en leads los checkouts de suscripción que no se pagaron';

    public function handle(): int
    {
        $this->info('Nuevos leads de checkout: ' . Suscripciones::marcarAbandonados());

        return self::SUCCESS;
    }
}
