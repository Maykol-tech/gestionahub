<?php

namespace App\Support;

use App\Models\BotMenu;
use App\Models\WhatsappSesion;

/**
 * Menus numerados (GEST-30). Flujo:
 * 1) El cliente escribe la palabra del menu y el bot responde con las opciones numeradas.
 * 2) La sesion guarda el menu abierto durante 30 minutos.
 * 3) Si el cliente responde con un numero, el bot devuelve la respuesta de esa opcion.
 */
class BotMenus
{
    public static function responder(int $tenantId, WhatsappSesion $sesion, string $mensaje): ?string
    {
        $texto = BotFlujos::normalizar($mensaje);
        $contexto = $sesion->contexto ?? [];

        // Respuesta numerica dentro de un menu abierto
        if (preg_match('/^\d{1,2}$/', $texto) && isset($contexto['menu_id'], $contexto['abierto_at'])) {
            $abierto = \Carbon\Carbon::parse($contexto['abierto_at']);

            if ($abierto->gte(now()->subMinutes(WhatsappSesion::MINUTOS_ACTIVA))) {
                $menu = BotMenu::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($contexto['menu_id']);
                $opcion = collect($menu?->opciones ?? [])->firstWhere('numero', (int) $texto);

                if ($opcion) {
                    self::cerrar($sesion);

                    return (string) $opcion['respuesta'];
                }
            }
        }

        // Palabra que abre un menu
        if ($texto === '') {
            return null;
        }

        $menu = BotMenu::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)
            ->get()->first(fn (BotMenu $m) => BotFlujos::normalizar($m->disparador) === $texto
                || str_contains($texto, BotFlujos::normalizar($m->disparador)));

        if (! $menu) {
            return null;
        }

        $sesion->update(['contexto' => ['menu_id' => $menu->id, 'abierto_at' => now()->toIso8601String()]]);

        $lineas = collect($menu->opciones ?? [])->map(fn ($o) => "{$o['numero']}. {$o['texto']}")->implode("\n");

        return $menu->titulo . "\n\n" . $lineas . "\n\nResponde con el número de tu opción.";
    }

    private static function cerrar(WhatsappSesion $sesion): void
    {
        $sesion->update(['contexto' => null]);
    }
}
