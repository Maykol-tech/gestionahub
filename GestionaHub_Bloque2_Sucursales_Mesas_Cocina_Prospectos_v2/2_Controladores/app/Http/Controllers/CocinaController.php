<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Support\Comandas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Panel de cocina: comandas en curso. La vista pide el fragmento cada 5 s para verlas en tiempo casi real. */
class CocinaController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        return view('admin.modulos.cocina', [
            'titulo' => 'Cocina',
            'modulo' => 'cocina',
            'comandas' => $this->enCurso(),
        ]);
    }

    /** Solo el bloque de comandas, para refrescarlo sin recargar la pagina. */
    public function fragmento()
    {
        return view('admin.modulos._cocina_grid', ['comandas' => $this->enCurso()]);
    }

    private function enCurso()
    {
        return Comanda::query()->with('mesa:id,nombre')
            ->whereIn('estado', ['pendiente', 'en_preparacion', 'lista'])
            ->orderBy('created_at')->get();
    }

    public function estado(Request $request, Comanda $comanda): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', Comanda::ESTADOS)]]);

        Comandas::cambiarEstado($comanda, $datos['estado']);

        return back();
    }
}
