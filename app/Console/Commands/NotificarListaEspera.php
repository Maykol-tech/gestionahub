<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Models\ListaEsperaCita;
use App\Models\WhatsappConexion;
use App\Support\WhatsAppNegocio;
use Illuminate\Console\Command;

/** Avisa a la primera persona en espera cuando se cancela una cita de ese dia. Corre cada 10 minutos. */
class NotificarListaEspera extends Command
{
    protected $signature = 'citas:lista-espera';

    protected $description = 'Avisa por WhatsApp a la lista de espera cuando se libera un horario';

    public function handle(): int
    {
        $canceladas = Cita::withoutGlobalScopes()
            ->where('estado', 'cancelada')
            ->where('updated_at', '>=', now()->subMinutes(20))
            ->get();

        foreach ($canceladas as $cita) {
            // Ya se avisó por esta cita: no repetir
            if (ListaEsperaCita::withoutGlobalScopes()->where('cita_origen_id', $cita->id)->exists()) {
                continue;
            }

            $conexion = WhatsappConexion::withoutGlobalScopes()
                ->where('tenant_id', $cita->tenant_id)->where('activo', true)->first();

            if (! $conexion) {
                continue;
            }

            $espera = ListaEsperaCita::withoutGlobalScopes()
                ->where('tenant_id', $cita->tenant_id)
                ->where('estado', 'esperando')
                ->whereDate('fecha_deseada', $cita->inicio->toDateString())
                ->where(fn ($q) => $q->whereNull('servicio_id')->orWhere('servicio_id', $cita->servicio_id))
                ->orderBy('created_at')
                ->first();

            if (! $espera) {
                continue;
            }

            $texto = "Hola {$espera->cliente_nombre}, se liberó un horario el {$cita->inicio->format('d/m')} a las "
                . $cita->inicio->format('H:i') . '. Responde a este mensaje si quieres reservarlo.';

            $ok = WhatsAppNegocio::enviarTexto($conexion, $espera->cliente_telefono, $texto, 'lista_espera');

            $espera->update([
                'estado' => 'avisado',
                'cita_origen_id' => $cita->id,
                'avisado_at' => $ok ? now() : null,
            ]);

            $this->info("Lista de espera #{$espera->id} avisada.");
        }

        return self::SUCCESS;
    }
}
