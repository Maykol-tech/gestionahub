<?php

namespace App\Http\Controllers;

use App\Models\BotMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Administracion de menus numerados del bot (GEST-30). Cada opcion va en una linea: numero|texto|respuesta. */
class BotMenuController extends DashboardController
{
    public function index() { return $this->pagina(); } public function pagina()
    {
        $this->compartirLayout();

        return view('admin.modulos.bot_menus', [
            'titulo' => 'Menús del bot',
            'modulo' => 'bot',
            'menus' => BotMenu::query()->orderBy('id')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'disparador' => ['required', 'string', 'max:80'],
            'titulo' => ['required', 'string', 'max:500'],
            'opciones' => ['required', 'string', 'max:4000'],
        ]);

        $opciones = $this->parsear($datos['opciones']);

        BotMenu::create([
            'tenant_id' => auth()->user()->tenant_id,
            'disparador' => $datos['disparador'],
            'titulo' => $datos['titulo'],
            'opciones' => $opciones,
            'activo' => true,
        ]);

        return back()->with('ok', 'Menú guardado.');
    }

    public function alternar(BotMenu $menu): RedirectResponse
    {
        $menu->update(['activo' => ! $menu->activo]);

        return back()->with('ok', $menu->activo ? 'Menú activado.' : 'Menú pausado.');
    }

    /** Convierte "1|Ver precios|Nuestros precios son..." en [{numero, texto, respuesta}]. */
    private function parsear(string $texto): array
    {
        $opciones = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($texto)) as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $partes = array_map('trim', explode('|', $linea, 3));
            if (count($partes) !== 3 || ! ctype_digit($partes[0]) || $partes[1] === '' || $partes[2] === '') {
                throw ValidationException::withMessages([
                    'opciones' => "Línea inválida: \"{$linea}\". Usa el formato numero|texto|respuesta.",
                ]);
            }

            $opciones[] = ['numero' => (int) $partes[0], 'texto' => $partes[1], 'respuesta' => $partes[2]];
        }

        if (count($opciones) < 2) {
            throw ValidationException::withMessages(['opciones' => 'Un menú necesita al menos dos opciones.']);
        }

        return $opciones;
    }
}
