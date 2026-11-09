<?php

namespace App\Http\Controllers;

use App\Models\IngresoManual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Registro de ingresos a mano (GEST-66). Se suman a los ingresos de Finanzas. */
class IngresosManualesController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $inicioMes = now()->startOfMonth()->toDateString();

        return view('admin.modulos.ingresos_manuales', [
            'titulo' => 'Ingresos manuales',
            'modulo' => 'reportes',
            'ingresos' => IngresoManual::query()->orderByDesc('fecha')->orderByDesc('id')->limit(100)->get(),
            'totalMes' => (float) IngresoManual::query()->where('fecha', '>=', $inicioMes)->sum('monto'),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'concepto' => ['required', 'string', 'max:120'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'categoria' => ['nullable', 'string', 'max:40'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        IngresoManual::create($datos + ['tenant_id' => auth()->user()->tenant_id]);

        return back()->with('ok', 'Ingreso registrado.');
    }

    public function eliminar(IngresoManual $ingreso): RedirectResponse
    {
        $ingreso->delete();

        return back()->with('ok', 'Ingreso eliminado.');
    }
}
