<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Recompensa;
use App\Support\AjustesNegocio;
use App\Support\BoletinInactivos;
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
            'beneficio' => $n['beneficio'] ?? '',
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
            'programa' => Fidelizacion::programa((int) auth()->user()->tenant_id),
            'boletin' => BoletinInactivos::config((int) auth()->user()->tenant_id),
            'boletinResumen' => BoletinInactivos::resumen((int) auth()->user()->tenant_id),
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
            'niveles.*.beneficio' => ['nullable', 'string', 'max:120'],
            'fid_activo' => ['sometimes', 'boolean'],
            'fid_puntos_por_sol' => ['sometimes', 'required', 'numeric', 'between:0.1,100'],
            'fid_valor_punto' => ['sometimes', 'required', 'numeric', 'between:0.001,100'],
            'fid_tope_pct' => ['sometimes', 'required', 'integer', 'between:0,100'],
            'fid_vence_meses' => ['sometimes', 'required', 'integer', 'between:0,60'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'fid_puntos_por_sol.between' => 'Los puntos por cada S/ 1 van de 0.1 a 100.',
            'fid_valor_punto.between' => 'El valor del punto va de S/ 0.001 a S/ 100.',
            'fid_tope_pct.between' => 'El tope de canje va de 0 % a 100 %.',
            'fid_vence_meses.between' => 'El vencimiento va de 0 (nunca) a 60 meses.',
        ]);

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
                'beneficio' => trim((string) ($porClave[$clave]['beneficio'] ?? '')),
            ];
        }

        if ($request->has('fid_puntos_por_sol')) {
            AjustesNegocio::guardar((int) auth()->user()->tenant_id, [
                'fid_activo' => $request->boolean('fid_activo'),
                'fid_puntos_por_sol' => round((float) $datos['fid_puntos_por_sol'], 2),
                'fid_valor_punto' => round((float) $datos['fid_valor_punto'], 3),
                'fid_tope_pct' => (int) $datos['fid_tope_pct'],
                'fid_vence_meses' => (int) $datos['fid_vence_meses'],
            ]);
        }

        DB::table('tenants')->where('id', auth()->user()->tenant_id)
            ->update(['niveles_fidelizacion' => json_encode($guardar, JSON_UNESCAPED_UNICODE)]);

        return back()->with('ok', 'Programa de puntos guardado.');
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

    /** Historial de puntos de un cliente con el saldo resultante (GEST-76). */
    public function movimientos(int $id)
    {
        $cliente = CrmCliente::query()->findOrFail($id);
        $movs = FidelizacionMovimiento::query()->where('cliente_id', $cliente->id)->orderByDesc('id')->limit(100)->get()
            ->map(fn ($m) => [
                'fecha' => $m->created_at?->timezone('America/Lima')->format('d/m/Y H:i'),
                'tipo' => $m->tipo, 'puntos' => (int) $m->puntos, 'descripcion' => $m->descripcion,
                'saldo' => $m->saldo_resultante,
            ]);

        return response()->json(['cliente' => $cliente->nombre, 'saldo' => (int) $cliente->puntos_saldo, 'movimientos' => $movs]);
    }

    /** Configura el boletín a clientes inactivos (GEST-78). */
    public function guardarBoletin(Request $request)
    {
        $datos = $request->validate([
            'boletin_activo' => ['sometimes', 'boolean'],
            'boletin_texto' => ['nullable', 'string', 'max:1000', 'required_if:boletin_activo,1'],
            'boletin_dias' => ['required', 'integer', 'between:7,365'],
            'boletin_cupon' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]*$/'],
        ], [
            'boletin_texto.required_if' => 'Escribe el mensaje para activar el boletín.',
            'boletin_dias.between' => 'Los días sin compra van de 7 a 365.',
            'boletin_cupon.regex' => 'El cupón solo puede tener letras, números, guion y guion bajo.',
        ]);

        $tenantId = (int) auth()->user()->tenant_id;
        DB::table('tenants')->where('id', $tenantId)->update([
            'boletin_activo' => $request->boolean('boletin_activo'),
            'boletin_texto' => $datos['boletin_texto'] ?? null,
            'updated_at' => now(),
        ]);
        AjustesNegocio::guardar($tenantId, [
            'boletin_dias' => (int) $datos['boletin_dias'],
            'boletin_cupon' => strtoupper((string) ($datos['boletin_cupon'] ?? '')),
        ]);

        return back()->with('ok', 'Boletín guardado. Se envía cada lunes a los clientes sin compras en ' . $datos['boletin_dias'] . ' días.');
    }

    /** Envía el boletín ahora mismo (sin esperar al lunes). */
    public function enviarBoletin()
    {
        $tenantId = (int) auth()->user()->tenant_id;
        if (trim(BoletinInactivos::config($tenantId)['texto']) === '') {
            return back()->withErrors(['boletin_texto' => 'Escribe y guarda el mensaje antes de enviar.']);
        }
        [$registrados, $enviados] = BoletinInactivos::enviar($tenantId);

        $msg = $registrados === 0
            ? 'No hay clientes inactivos pendientes (o ya recibieron el boletín en los últimos 30 días).'
            : "Boletín procesado para {$registrados} clientes: {$enviados} mensajes de WhatsApp enviados."
                . ($enviados < $registrados ? ' Los demás quedaron registrados sin envío (revisa la conexión de WhatsApp).' : '');

        return back()->with('ok', $msg);
    }
}
