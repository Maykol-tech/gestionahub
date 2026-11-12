<?php
namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Http\Request;

class HorariosController extends Controller {
    public function index() {
        $horarios = Horario::orderBy('dia_semana')->orderBy('apertura')->get();
        return view('admin.modulos.horarios', compact('horarios'));
    }

    public function update(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $shifts = $request->input('shifts', []);

        // Eliminar todos los horarios actuales del tenant y volver a insertar
        Horario::where('tenant_id', $tenantId)->delete();

        foreach ($shifts as $dia => $turnos) {
            foreach ($turnos as $turno) {
                if (!empty($turno['apertura']) && !empty($turno['cierre'])) {
                    Horario::create([
                        'tenant_id' => $tenantId,
                        'dia_semana' => (int) $dia,
                        'apertura' => $turno['apertura'],
                        'cierre' => $turno['cierre'],
                    ]);
                }
            }
        }

        return back()->with('success', 'Horarios actualizados correctamente.');
    }
}