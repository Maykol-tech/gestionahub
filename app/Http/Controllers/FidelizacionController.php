<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Recompensa;
use App\Support\CrudModulos;
use App\Support\Fidelizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Pagina de Fidelizacion: clientes con puntos y niveles, canje de recompensas y ajustes. */
class FidelizacionController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $config = Fidelizacion::nivelesDe((int) auth()->user()->tenant_id);

        $clientes = CrmCliente::query()
            ->where('puntos_historicos', '>', 0)->orWhere('puntos_saldo', '>', 0)
            ->orderByDesc('puntos_saldo')->limit(200)->get()
            ->map(fn (CrmCliente $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'telefono' => $c->telefono,
                'saldo' => (int) $c->puntos_saldo,
                'historicos' => (int) $c->puntos_historicos,
                'nivel' => Fidelizacion::nivelDe((int) $c->puntos_historicos, $config),
            ])->values();

        $niveles = collect($config)->map(fn ($n, $clave) => [
            'clave' => $clave, 'nombre' => $n['nombre'], 'color' => $n['color'], 'fondo' => $n['fondo'],
            'desde' => $n['desde'], 'multiplicador' => $n['multiplicador'],
            'clientes' => $clientes->where('nivel', $clave)->count(),
        ])->values();

        $recompensas = Recompensa::query()->where('activo', true)->orderBy('puntos_requeridos')
            ->get(['id', 'nombre', 'puntos_requeridos', 'descuento_pct']);

        return view('admin.modulos.fidelizacion', [
            'titulo' => 'Fidelización',
            'modulo' => 'fidelizacion',
            'clientes' => $clientes,
            'niveles' => $niveles,
            'recompensas' => $recompensas,
            'stats' => [
                'clientes' => $clientes->count(),
                'puntos_vigentes' => (int) $clientes->sum('saldo'),
                'puntos_emitidos' => (int) FidelizacionMovimiento::query()->where('tipo', 'ganado')->sum('puntos'),
                'canjes' => (int) FidelizacionMovimiento::query()->where('tipo', 'canje')->count(),
            ],
            'cfgRecompensas' => CrudModulos::cliente('fidelizacion'),
        ]);
    }

    /** Configura nombre, punto de inicio y multiplicador de cada nivel (GEST-75). */
    public function guardarNiveles(Request $request)
    {
        $datos = $request->validate([
            'niveles' => ['required', 'array', 'size:3'],
            'niveles.*.clave' => ['required', 'in:bronce,plata,oro'],
            'niveles.*.nombre' => ['required', 'string', 'max:30'],
            'niveles.*.desde' => ['required', 'integer', 'between:0,1000000'],
            'niveles.*.multiplicador' => ['required', 'numeric', 'between:1,5'],
        ], ['required' => 'Este campo es obligatorio.']);

        $porClave = collect($datos['niveles'])->keyBy('clave');
        if (count($porClave) !== 3) {
            return back()->withErrors(['niveles' => 'Cada nivel debe aparecer una sola vez.']);
        }

        if ((int) $porClave['bronce']['desde'] !== 0) {
            return back()->withErrors(['niveles' => 'Bronce siempre empieza en 0 puntos.']);
        }
        if (! ((int) $porClave['bronce']['desde'] < (int) $porClave['plata']['desde']
            && (int) $porClave['plata']['desde'] < (int) $porClave['oro']['desde'])) {
            return back()->withErrors(['niveles' => 'Los puntos de inicio deben subir de Bronce a Plata y de Plata a Oro.']);
        }

        $guardar = [];
        foreach (['bronce', 'plata', 'oro'] as $clave) {
            $guardar[$clave] = [
                'nombre' => $porClave[$clave]['nombre'],
                'desde' => (int) $porClave[$clave]['desde'],
                'multiplicador' => round((float) $porClave[$clave]['multiplicador'], 2),
            ];
        }

        DB::table('tenants')->where('id', auth()->user()->tenant_id)
            ->update(['niveles_fidelizacion' => json_encode($guardar, JSON_UNESCAPED_UNICODE)]);

        return back()->with('ok', 'Niveles de fidelización guardados.');
    }

    public function canjear(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'recompensa_id' => ['required', 'integer'],
        ]);

        $cliente = CrmCliente::query()->findOrFail($datos['cliente_id']);
        $recompensa = Recompensa::query()->where('activo', true)->findOrFail($datos['recompensa_id']);

        Fidelizacion::canjear($cliente, $recompensa);

        return response()->json(['success' => true, 'message' => "Canjeaste «{$recompensa->nombre}» para {$cliente->nombre}."]);
    }

    public function ajustar(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'puntos' => ['required', 'integer', 'between:-100000,100000', 'not_in:0'],
            'motivo' => ['required', 'string', 'max:200'],
        ], [
            'puntos.not_in' => 'Los puntos no pueden ser cero.',
            'puntos.between' => 'El ajuste debe estar entre -100000 y 100000 puntos.',
            'required' => 'Este campo es obligatorio.',
        ]);

        $cliente = CrmCliente::query()->findOrFail($datos['cliente_id']);
        Fidelizacion::ajustar($cliente, (int) $datos['puntos'], $datos['motivo']);

        return response()->json(['success' => true, 'message' => 'Puntos actualizados.']);
    }
}
