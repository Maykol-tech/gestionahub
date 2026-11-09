<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Models\WhatsappConexion;
use App\Support\WhatsAppNegocio;
use Illuminate\Console\Command;

/** Envia por WhatsApp (desde el numero de cada negocio) el recordatorio de las citas de manana. */
class RecordatoriosCitas extends Command
{
    protected $signature = 'citas:recordatorios';

    protected $description = 'Envía por WhatsApp el recordatorio de las citas de mañana.';

    public function handle(): int
    {
        $manana = now()->addDay();

        $citas = Cita::withoutGlobalScopes()->with('servicio')
            ->whereBetween('inicio', [$manana->copy()->startOfDay(), $manana->copy()->endOfDay()])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereNull('recordatorio_enviado_at')
            ->whereNotNull('cliente_telefono')
            ->get();

        $enviados = 0;

        foreach ($citas as $cita) {
            $conexion = WhatsappConexion::withoutGlobalScopes()
                ->where('tenant_id', $cita->tenant_id)->where('activo', true)->first();

            if (! $conexion) {
                continue; // El negocio aun no conecta su numero de WhatsApp.
            }

            $texto = 'Hola ' . $cita->cliente_nombre . ', te recordamos tu cita de '
                . ($cita->servicio?->nombre ?? 'servicio') . ' mañana a las ' . $cita->inicio->format('H:i')
                . '. Si necesitas cambiarla, responde a este mensaje.';

            if (WhatsAppNegocio::enviarTexto($conexion, (string) $cita->cliente_telefono, $texto)) {
                $cita->update(['recordatorio_enviado_at' => now()]);
                $enviados++;
            }
        }

        $this->info("Recordatorios enviados: {$enviados} de {$citas->count()} citas de mañana.");

        return self::SUCCESS;
    }
}
