<?php

namespace App\Http\Controllers;

use App\Models\BotMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Menus numerados del bot (GEST-30) con editor visual: cada opción es una fila (texto + respuesta)
 * y se ve una vista previa como en WhatsApp. Sigue aceptando el formato antiguo numero|texto|respuesta.
 */
class BotMenuController extends DashboardController
{
    public function pagina()
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
        $datos = $this->validar($request);
        BotMenu::create($datos + ['tenant_id' => auth()->user()->tenant_id, 'activo' => true]);

        return back()->with('ok', 'Menú "' . $datos['disparador'] . '" guardado.');
    }

    public function actualizar(Request $request, BotMenu $menu): RedirectResponse
    {
        $menu->update($this->validar($request, $menu->id));

        return back()->with('ok', 'Menú "' . $menu->disparador . '" actualizado.');
    }

    public function eliminar(BotMenu $menu): RedirectResponse
    {
        $menu->delete();

        return back()->with('ok', 'Menú eliminado.');
    }

    public function alternar(BotMenu $menu): RedirectResponse
    {
        $menu->update(['activo' => ! $menu->activo]);

        return back()->with('ok', $menu->activo ? 'Menú activado.' : 'Menú pausado.');
    }

    private function validar(Request $request, ?int $ignorar = null): array
    {
        $datos = $request->validate([
            'disparador' => ['required', 'string', 'max:80'],
            'titulo' => ['required', 'string', 'max:500'],
            'opciones' => ['required'],
            'opciones.*.texto' => ['nullable', 'string', 'max:120'],
            'opciones.*.respuesta' => ['nullable', 'string', 'max:1000'],
        ], [
            'disparador.required' => 'Escribe la palabra que abre el menú.',
            'titulo.required' => 'Escribe el mensaje de bienvenida.',
            'opciones.required' => 'Agrega al menos dos opciones.',
        ]);

        $disparador = mb_strtolower(trim($datos['disparador']));
        $repetido = BotMenu::query()->whereRaw('lower(disparador) = ?', [$disparador])
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))->exists();
        if ($repetido) {
            throw ValidationException::withMessages(['disparador' => "Ya tienes un menú que se abre con \"{$datos['disparador']}\"."]);
        }

        $opciones = is_array($datos['opciones']) ? $this->desdeFilas($datos['opciones']) : $this->parsear((string) $datos['opciones']);
        if (count($opciones) < 2) {
            throw ValidationException::withMessages(['opciones' => 'Un menú necesita al menos dos opciones.']);
        }
        if (count($opciones) > 9) {
            throw ValidationException::withMessages(['opciones' => 'Usa como máximo 9 opciones para que el cliente responda con un solo número.']);
        }

        return ['disparador' => trim($datos['disparador']), 'titulo' => trim($datos['titulo']), 'opciones' => $opciones];
    }

    /** Filas del editor visual: se numeran solas en el orden en que están. */
    private function desdeFilas(array $filas): array
    {
        $opciones = [];
        foreach (array_values($filas) as $f) {
            $texto = trim((string) ($f['texto'] ?? ''));
            $respuesta = trim((string) ($f['respuesta'] ?? ''));
            if ($texto === '' && $respuesta === '') {
                continue;
            }
            if ($texto === '' || $respuesta === '') {
                throw ValidationException::withMessages(['opciones' => 'Cada opción necesita su texto y su respuesta (opción ' . (count($opciones) + 1) . ').']);
            }
            $opciones[] = ['numero' => count($opciones) + 1, 'texto' => $texto, 'respuesta' => $respuesta];
        }

        return $opciones;
    }

    /** Formato antiguo: "1|Ver precios|Nuestros precios son..." por línea. */
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

        return $opciones;
    }
}
