<?php

namespace App\Console\Commands;

use App\Support\Prueba;
use Illuminate\Console\Command;

/** GEST-91: avisa por correo cuando a la prueba gratis le quedan 7, 3 y 1 días. */
class AvisosPrueba extends Command
{
    protected $signature = 'prueba:avisos';

    protected $description = 'Envía los avisos de fin de la prueba gratis (7, 3 y 1 días)';

    public function handle(): int
    {
        $this->info('Avisos enviados: ' . Prueba::enviarAvisos());

        return self::SUCCESS;
    }
}
