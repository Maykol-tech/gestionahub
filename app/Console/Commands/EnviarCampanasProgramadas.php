<?php

namespace App\Console\Commands;

use App\Models\CampanaWhatsapp;
use App\Support\Campanas;
use Illuminate\Console\Command;
use Throwable;

/** Envia las campañas programadas cuya fecha y hora ya llegaron. Se ejecuta desde el scheduler cada minuto. */
class EnviarCampanasProgramadas extends Command
{
    protected $signature = 'campanas:enviar-programadas';

    protected $description = 'Envia las campañas de WhatsApp programadas cuya hora ya llegó';

    public function handle(): int
    {
        $campanas = CampanaWhatsapp::withoutGlobalScopes()
            ->where('estado', 'programada')
            ->where('programada_para', '<=', now())
            ->orderBy('programada_para')
            ->limit(20)
            ->get();

        foreach ($campanas as $campana) {
            try {
                Campanas::enviar($campana);
                $this->info("Campaña #{$campana->id} enviada.");
            } catch (Throwable $e) {
                // Si falla (por ejemplo, sin conexión), se deja programada para el siguiente intento
                $this->warn("Campaña #{$campana->id} no se pudo enviar: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
