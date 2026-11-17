<?php

namespace App\Console\Commands;

use App\Support\Suscripciones;
use Illuminate\Console\Command;

/** GEST-94: renueva con la tarjeta guardada (Niubiz) las suscripciones que vencen; reintenta 3 veces. */
class CobrarSuscripciones extends Command
{
    protected $signature = 'suscripciones:cobrar';

    protected $description = 'Cobra las renovaciones automáticas de las suscripciones con tarjeta';

    public function handle(): int
    {
        [$ok, $mal] = Suscripciones::cobrarRenovaciones();
        $this->info("Renovadas: {$ok} · fallidas: {$mal}");

        return self::SUCCESS;
    }
}
