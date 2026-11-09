<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\Horarios;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Horarios de atencion del negocio. Alimentan el indicador de abierto o cerrado. */
class HorariosController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $tenant = Tenant::query()->findOrFail(auth()->user()->tenant_id);

        return view('admin.modulos.horarios', [
            'titulo' => 'Horarios de atención',
            'modulo' => 'horarios',
            'horarios' => $this->leer($tenant->horarios),
            'abiertoAhora' => Horarios::abiertoAhora($this->leer($tenant->horarios)),
        ]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'horarios' => ['required', 'array', 'size:7'],
            'horarios.*.dia' => ['required', 'integer', 'between:0,6'],
            'horarios.*.activo' => ['required', 'boolean'],
            'horarios.*.abre' => ['required', 'date_format:H:i'],
            'horarios.*.cierra' => ['required', 'date_format:H:i'],
        ], [
            'horarios.size' => 'Indica los 7 días de la semana.',
            'horarios.*.abre.date_format' => 'La hora de apertura debe tener formato HH:MM.',
            'horarios.*.cierra.date_format' => 'La hora de cierre debe tener formato HH:MM.',
        ]);

        foreach ($datos['horarios'] as $h) {
            if ($h['activo'] && $h['abre'] >= $h['cierra']) {
                return response()->json([
                    'success' => false,
                    'message' => 'En los días abiertos, la hora de cierre debe ser después de la de apertura.',
                ], 422);
            }
        }

        DB::table('tenants')->where('id', $request->user()->tenant_id)
            ->update(['horarios' => json_encode(Horarios::normalizar($datos['horarios']))]);

        return response()->json(['success' => true, 'message' => 'Horarios guardados.']);
    }

    /** Lee los horarios guardados (JSON) o devuelve el horario por defecto. */
    private function leer($valor): array
    {
        $lista = is_string($valor) ? json_decode($valor, true) : $valor;

        return is_array($lista) && count($lista) === 7 ? Horarios::normalizar($lista) : Horarios::porDefecto();
    }
}
