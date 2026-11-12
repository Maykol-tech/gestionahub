<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Tenant;
use App\Support\AjustesNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modulos del menu: Control, Presencia y Cuenta.
 * Solo muestra datos reales del tenant; si un modulo aun no tiene tablas,
 * la vista enseña un estado vacio (nunca datos inventados).
 */
class ModulosController extends DashboardController
{
    /** Catalogo base de planes (se usa solo si la tabla planes_saas esta vacia). */
    private const PLANES_BASE = [
        ['slug' => 'emprendedor', 'nombre' => 'Emprendedor', 'precio' => 49, 'beneficios' => ['1 sucursal', 'Hasta 200 pedidos al mes', 'Catálogo web y punto de venta', '2 usuarios'], 'faltan' => ['Delivery propio', 'WhatsApp API', 'Multi-sucursal']],
        ['slug' => 'negocio', 'nombre' => 'Negocio', 'precio' => 89, 'beneficios' => ['Hasta 2 sucursales', 'Hasta 1,000 pedidos al mes', 'Delivery propio', '5 usuarios', 'Mailing básico'], 'faltan' => ['WhatsApp API', 'Sucursales ilimitadas']],
        ['slug' => 'pro', 'nombre' => 'Pro', 'precio' => 149, 'beneficios' => ['Sucursales ilimitadas', 'Pedidos y usuarios ilimitados', 'WhatsApp API y chatbot', 'Pasarelas de pago', 'Soporte prioritario'], 'faltan' => []],
    ];

    private const METODOS = [
        'efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'tarjeta' => 'Tarjeta',
        'mercadopago' => 'Mercado Pago', 'transferencia' => 'Transferencia',
    ];

    private const CANALES = ['web' => 'Web', 'whatsapp' => 'WhatsApp', 'pos' => 'Punto de venta', 'app' => 'App', 'manual' => 'Manual'];

    // =================================================================
    // PAGINA DE CADA MODULO
    // =================================================================

    public function mostrar(Request $request, string $modulo)
    {
        abort_unless(isset(self::MODULOS[$modulo]), 404);

        $vista = 'admin.modulos.' . $modulo;
        if (! view()->exists($vista)) {
            return $this->modulo($modulo);   // aun sin vista propia: pagina "en preparacion"
        }

        $this->compartirLayout();
        [$titulo, $icono, $grupo] = self::MODULOS[$modulo];
        $metodo = 'datos' . str_replace('_', '', ucwords($modulo, '_'));
        $datos = method_exists($this, $metodo) ? $this->{$metodo}($request) : [];

        return view($vista, array_merge(compact('modulo', 'titulo', 'icono', 'grupo'), $datos));
    }

    // =================================================================
    // CONTROL
    // =================================================================

    protected function datosCaja(Request $request): array
    {
        $fecha = $this->fechaValida($request->query('fecha')) ?? now();
        $movimientos = collect();
        $porCobrar = 0.0;

        if ($this->hayPedidos()) {
            $pedidos = $this->pedidosQuery()
                ->where('pedidos.estado', '!=', 'cancelado')
                ->whereDate('pedidos.created_at', $fecha->toDateString())
                ->orderBy('pedidos.created_at')->get();

            foreach ($pedidos as $p) {
                if (! $this->estaPagado($p)) {
                    $porCobrar += (float) $p->total;
                    continue;
                }
                $movimientos->push([
                    'hora' => Carbon::parse($p->created_at)->format('H:i'),
                    'concepto' => 'Pedido #' . ($p->numero ?? $p->id),
                    'detalle' => $p->cliente_nombre ?? 'Cliente sin registrar',
                    'metodo' => $p->metodo_pago ?? 'efectivo',
                    'metodo_texto' => self::METODOS[$p->metodo_pago ?? 'efectivo'] ?? ucfirst((string) $p->metodo_pago),
                    'tipo' => 'ingreso',
                    'monto' => (float) $p->total,
                ]);
            }
        }

        $ingresos = $movimientos->sum('monto');
        $egresos = 0.0; // aun no existe la tabla de gastos
        $porMetodo = $movimientos->groupBy('metodo')->map(fn ($m, $k) => [
            'clave' => $k, 'texto' => self::METODOS[$k] ?? ucfirst($k), 'monto' => $m->sum('monto'), 'cantidad' => $m->count(),
        ])->values();

        return [
            'fecha' => $fecha->toDateString(),
            'fechaTexto' => ucfirst($fecha->translatedFormat('l j \d\e F')),
            'esHoy' => $fecha->isToday(),
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'resultado' => $ingresos - $egresos,
            'porCobrar' => $porCobrar,
            'movimientos' => $movimientos->values(),
            'porMetodo' => $porMetodo,
            'hayPedidos' => $this->hayPedidos(),
        ];
    }

    protected function datosReportes(Request $request): array
    {
        $rango = (int) $request->query('rango', 7);
        $rango = in_array($rango, [7, 30, 90], true) ? $rango : 7;
        $desde = now()->startOfDay()->subDays($rango - 1);
        $anteriorDesde = $desde->copy()->subDays($rango);

        $pedidos = collect();
        $ventasAnterior = 0.0;
        if ($this->hayPedidos()) {
            $pedidos = $this->pedidosQuery()->where('pedidos.estado', '!=', 'cancelado')
                ->where('pedidos.created_at', '>=', $desde)->get();
            $ventasAnterior = (float) DB::table('pedidos')->where('tenant_id', $this->tenantId())
                ->where('estado', '!=', 'cancelado')
                ->whereBetween('created_at', [$anteriorDesde, $desde])->sum('total');
        }

        $ventas = (float) $pedidos->sum('total');
        $cantidad = $pedidos->count();

        // Serie del grafico: por dia (7 y 30) o por semana (90)
        $serie = [];
        $porSemana = $rango === 90;
        $paso = $porSemana ? 7 : 1;
        $montos = array_fill(0, (int) ceil($rango / $paso), 0.0);
        foreach ($pedidos as $p) {
            $i = intdiv((int) $desde->diffInDays(Carbon::parse($p->created_at)->startOfDay()), $paso);
            if (isset($montos[$i])) {
                $montos[$i] += (float) $p->total;
            }
        }
        foreach ($montos as $i => $monto) {
            $ini = $desde->copy()->addDays($i * $paso);
            $serie[] = [
                'etiqueta' => $porSemana ? $ini->format('d/m') : ($rango === 7 ? ucfirst($ini->translatedFormat('D')) : $ini->format('d')),
                'detalle' => $porSemana ? 'Semana del ' . $ini->format('d/m') : ucfirst($ini->translatedFormat('D d/m')),
                'monto' => round($monto, 2),
            ];
        }

        $agrupar = fn ($campo, $nombres) => $pedidos->groupBy(fn ($p) => $p->{$campo} ?? 'manual')
            ->map(fn ($g, $k) => ['texto' => $nombres[$k] ?? ucfirst((string) $k), 'monto' => (float) $g->sum('total'), 'cantidad' => $g->count()])
            ->sortByDesc('monto')->values();

        return [
            'rango' => $rango,
            'desdeTexto' => $desde->format('d/m/Y'),
            'hastaTexto' => now()->format('d/m/Y'),
            'ventas' => $ventas,
            'cantidad' => $cantidad,
            'ticket' => $cantidad ? $ventas / $cantidad : 0,
            'clientesUnicos' => $pedidos->pluck('cliente_id')->filter()->unique()->count(),
            'variacion' => $ventasAnterior > 0 ? round(($ventas - $ventasAnterior) * 100 / $ventasAnterior) : null,
            'serie' => $serie,
            'porCanal' => $this->columnaPedido('canal') ? $agrupar('canal', self::CANALES) : collect(),
            'porMetodo' => $this->columnaPedido('metodo_pago') ? $agrupar('metodo_pago', self::METODOS) : collect(),
            'hayPedidos' => $this->hayPedidos(),
        ];
    }

    protected function datosCobros(Request $request): array
    {
        $lista = collect();
        if ($this->hayPedidos()) {
            $lista = $this->pedidosQuery()->where('pedidos.estado', '!=', 'cancelado')
                ->orderByDesc('pedidos.id')->limit(300)->get()
                ->map(fn ($p) => $this->formatearCobro($p));
        }

        return [
            'cobros' => $lista->values(),
            'cobrado' => $lista->where('pagado', true)->sum('total'),
            'porCobrar' => $lista->where('pagado', false)->sum('total'),
            'parcial' => 0.0, // aun no se registran pagos parciales
            'puedeMarcar' => $this->hayPedidos() && $this->columnaPedido('pagado'),
            'hayPedidos' => $this->hayPedidos(),
        ];
    }

    public function marcarCobrado($id)
    {
        abort_unless($this->hayPedidos() && $this->columnaPedido('pagado'), 404);

        $q = DB::table('pedidos')->where('tenant_id', $this->tenantId())->where('id', $id);
        abort_unless($q->exists(), 404);
        $q->update(['pagado' => true, 'updated_at' => now()]);

        $p = $this->pedidosQuery()->where('pedidos.id', $id)->first();

        return response()->json(['message' => 'Pedido #' . ($p->numero ?? $id) . ' marcado como cobrado', 'data' => $this->formatearCobro($p)]);
    }

    protected function datosRepartos(Request $request): array
    {
        $hayRepartidores = Schema::hasTable('repartidores');
        $colsRep = $hayRepartidores ? Schema::getColumnListing('repartidores') : [];
        $enlazado = $hayRepartidores && $this->hayPedidos() && $this->columnaPedido('repartidor_id');

        $enCamino = collect();
        $entregadosHoy = 0;
        if ($this->hayPedidos()) {
            $q = $this->pedidosQuery()->where('pedidos.estado', 'en_camino')->orderBy('pedidos.created_at');
            if ($enlazado) {
                $q->leftJoin('repartidores', 'repartidores.id', '=', 'pedidos.repartidor_id')
                  ->addSelect(DB::raw((in_array('nombre', $colsRep, true) ? 'repartidores.nombre' : 'NULL') . ' as repartidor_nombre'))
                  ->addSelect(DB::raw((in_array('telefono', $colsRep, true) ? 'repartidores.telefono' : 'NULL') . ' as repartidor_telefono'));
            }
            $enCamino = $q->get()->map(fn ($p) => array_merge($this->formatearPedido($p), [
                'pagado' => $this->estaPagado($p),
                'repartidor' => $p->repartidor_nombre ?? null,
                'repartidor_telefono' => $p->repartidor_telefono ?? null,
                'salida' => Carbon::parse($p->updated_at ?? $p->created_at)->format('H:i'),
            ]));

            $entregadosHoy = DB::table('pedidos')->where('tenant_id', $this->tenantId())
                ->where('estado', 'entregado')->where('updated_at', '>=', now()->startOfDay())->count();
        }

        $repartidores = collect();
        if ($hayRepartidores) {
            $q = DB::table('repartidores');
            if (in_array('tenant_id', $colsRep, true)) {
                $q->where('tenant_id', $this->tenantId());
            }
            $repartidores = $q->limit(100)->get()->map(fn ($r) => [
                'id' => $r->id,
                'nombre' => $r->nombre ?? ('Repartidor ' . $r->id),
                'telefono' => $r->telefono ?? null,
                'activo' => (bool) ($r->activo ?? true),
                'en_ruta' => $enCamino->where('repartidor', $r->nombre ?? null)->count(),
            ]);
        }

        return [
            'enCamino' => $enCamino->values(),
            'porCobrarRuta' => $enCamino->where('pagado', false)->sum('total'),
            'entregadosHoy' => $entregadosHoy,
            'repartidores' => $repartidores->values(),
            'hayRepartidores' => $hayRepartidores,
            'hayPedidos' => $this->hayPedidos(),
        ];
    }

    // =================================================================
    // PRESENCIA
    // =================================================================

    protected function datosVitrina(Request $request): array
    {
        $tenant = Tenant::find($this->tenantId());
        $productos = Producto::where('tenant_id', $this->tenantId())->where('activo', true)->count();
        $categorias = Categoria::where('tenant_id', $this->tenantId())->count();
        $conImagen = Producto::where('tenant_id', $this->tenantId())->where('activo', true)->whereNotNull('imagen_url')->count();

        $pasos = [
            ['Datos del negocio', 'Nombre y teléfono de contacto', (bool) ($tenant?->nombre_negocio && $tenant?->telefono), route('admin.modulo', 'personalizar')],
            ['Logo del negocio', 'Se muestra en la cabecera de tu vitrina', (bool) $tenant?->logo_url, route('admin.modulo', 'personalizar')],
            ['Productos publicados', $productos . ' publicados en ' . $categorias . ($categorias === 1 ? ' categoría' : ' categorías'), $productos > 0, route('admin.productos')],
            ['Fotos de productos', $conImagen . ' de ' . $productos . ' productos con foto', $productos > 0 && $conImagen === $productos, route('admin.productos')],
            ['Dominio propio', $tenant?->dominio ?: 'Aún no configurado', (bool) $tenant?->dominio, null],
        ];

        return ['pasos' => $pasos, 'avance' => (int) round(collect($pasos)->where(2, true)->count() * 100 / count($pasos))];
    }

    protected function datosTienda(Request $request): array
    {
        $tenant = Tenant::find($this->tenantId());
        $productos = Producto::with('categoria:id,nombre')->where('tenant_id', $this->tenantId())->where('activo', true)
            ->orderByDesc('id')->limit(8)->get()->map(fn ($p) => $this->formatearProducto($p));

        return [
            'negocio' => $tenant->nombre_negocio ?? 'Mi negocio',
            'dominio' => $tenant?->dominio,
            'productos' => $productos,
            'totalPublicados' => Producto::where('tenant_id', $this->tenantId())->where('activo', true)->count(),
        ];
    }

    protected function datosBot(Request $request): array
    {
        $tenant = Tenant::find($this->tenantId());
        $ajustes = AjustesNegocio::leer($this->tenantId());
        if ($ajustes['bot_numero'] === '' && $tenant?->telefono) {
            $ajustes['bot_numero'] = $tenant->telefono;
        }

        return [
            'ajustes' => $ajustes,
            'negocio' => $tenant->nombre_negocio ?? 'Mi negocio',
            'productos' => Producto::where('tenant_id', $this->tenantId())->where('activo', true)->count(),
        ];
    }

    public function guardarBot(Request $request)
    {
        $datos = $request->validate([
            'bot_activo' => ['boolean'],
            'bot_numero' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +()-]*$/'],
            'bot_bienvenida' => ['required', 'string', 'max:500'],
            'bot_horario' => ['nullable', 'string', 'max:120'],
            'bot_ausencia' => ['nullable', 'string', 'max:500'],
        ], self::MENSAJES);

        $datos['bot_activo'] = $request->boolean('bot_activo');
        $datos['bot_numero'] = (string) ($datos['bot_numero'] ?? '');
        $datos['bot_horario'] = (string) ($datos['bot_horario'] ?? '');
        $datos['bot_ausencia'] = (string) ($datos['bot_ausencia'] ?? '');

        return response()->json(['message' => 'Configuración del bot guardada', 'data' => AjustesNegocio::guardar($this->tenantId(), $datos)]);
    }

    // =================================================================
    // CUENTA
    // =================================================================

    protected function datosSucursales(Request $request): array
    {
        $tenant = Tenant::find($this->tenantId());
        $max = 1;
        if ($tenant?->plan_id && Schema::hasTable('planes_saas')) {
            $max = (int) (DB::table('planes_saas')->where('id', $tenant->plan_id)->value('max_sucursales') ?? 1);
        }

        return [
            'principal' => [
                'nombre' => $tenant->nombre_negocio ?? 'Mi negocio',
                'telefono' => $tenant?->telefono,
                'email' => $tenant?->email,
                'desde' => $tenant?->created_at ? Carbon::parse($tenant->created_at)->format('d/m/Y') : null,
            ],
            'maxSucursales' => $max,
        ];
    }

    protected function datosPlanes(Request $request): array
    {
        $tenant = Tenant::find($this->tenantId());
        $planes = collect();
        if (Schema::hasTable('planes_saas')) {
            $planes = DB::table('planes_saas')->where('activo', true)->orderBy('precio_mensual')->get()->map(function ($p) {
                $base = collect(self::PLANES_BASE)->firstWhere('slug', $p->slug) ?? ['beneficios' => [], 'faltan' => []];

                return ['id' => $p->id, 'slug' => $p->slug, 'nombre' => $p->nombre, 'precio' => (float) $p->precio_mensual,
                    'anual' => (float) $p->precio_anual, 'beneficios' => $base['beneficios'], 'faltan' => $base['faltan']];
            });
        }
        $desdeTabla = $planes->isNotEmpty();
        if (! $desdeTabla) {
            $planes = collect(self::PLANES_BASE)->map(fn ($p) => $p + ['id' => null, 'anual' => $p['precio'] * 10]);
        }

        $actual = null;
        if ($tenant?->plan_id && $desdeTabla) {
            $actual = $planes->firstWhere('id', $tenant->plan_id)['slug'] ?? null;
        }

        $diasPrueba = null;
        if ($tenant?->estado === 'trial' && $tenant->trial_ends_at) {
            $diasPrueba = max(0, (int) now()->diffInDays(Carbon::parse($tenant->trial_ends_at), false));
        }

        $pagos = collect();
        if (Schema::hasTable('pagos_saas')) {
            $pagos = DB::table('pagos_saas')->where('tenant_id', $this->tenantId())->orderByDesc('id')->limit(12)->get()
                ->map(fn ($p) => [
                    'fecha' => Carbon::parse($p->fecha_pago ?? $p->created_at)->format('d/m/Y'),
                    'pasarela' => $p->pasarela ? ucfirst($p->pasarela) : '—',
                    'monto' => (float) $p->monto,
                    'estado' => $p->estado,
                ]);
        }

        return [
            'planes' => $planes->values(),
            'actual' => $actual,
            'estado' => $tenant->estado ?? 'trial',
            'diasPrueba' => $diasPrueba,
            'venceTexto' => $tenant?->trial_ends_at ? Carbon::parse($tenant->trial_ends_at)->format('d/m/Y') : null,
            'pagos' => $pagos,
        ];
    }

    protected function datosPersonalizar(Request $request): array
    {
        $tenant = Tenant::find($this->tenantId());
        $ajustes = AjustesNegocio::leer($this->tenantId());

        return [
            'negocio' => [
                'nombre_negocio' => $tenant->nombre_negocio ?? '',
                'telefono' => $tenant->telefono ?? '',
                'ruc' => $tenant->ruc ?? '',
                'email' => $tenant->email ?? '',
                'color_menu' => $ajustes['color_menu'],
                'color_accion' => $ajustes['color_accion'],
            ],
            'porDefecto' => ['color_menu' => AjustesNegocio::POR_DEFECTO['color_menu'], 'color_accion' => AjustesNegocio::POR_DEFECTO['color_accion']],
        ];
    }

    public function guardarPersonalizar(Request $request)
    {
        $datos = $request->validate([
            'nombre_negocio' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +()-]*$/'],
            'ruc' => ['nullable', 'digits:11'],
            'color_menu' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_accion' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], self::MENSAJES + ['ruc.digits' => 'El RUC debe tener 11 dígitos.', 'color_menu.regex' => 'Color no válido.', 'color_accion.regex' => 'Color no válido.']);

        $tenant = Tenant::findOrFail($this->tenantId());
        $tenant->nombre_negocio = trim($datos['nombre_negocio']);
        $tenant->telefono = $datos['telefono'] ?: null;
        $tenant->ruc = $datos['ruc'] ?: null;
        $tenant->save();

        AjustesNegocio::guardar($this->tenantId(), [
            'color_menu' => strtoupper($datos['color_menu']),
            'color_accion' => strtoupper($datos['color_accion']),
        ]);

        return response()->json(['message' => 'Cambios guardados', 'data' => ['negocio' => $tenant->nombre_negocio, 'iniciales' => $this->iniciales($tenant->nombre_negocio)]]);
    }

    // =================================================================
    // AYUDANTES
    // =================================================================

    private function fechaValida(?string $valor): ?Carbon
    {
        if (! $valor || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }
        try {
            $f = Carbon::createFromFormat('Y-m-d', $valor)->startOfDay();

            return $f->isFuture() ? null : $f;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Pagado segun la columna "pagado"; si no existe, se considera cobrado al entregarse. */
    private function estaPagado(object $p): bool
    {
        return $this->columnaPedido('pagado') ? (bool) $p->pagado : ($p->estado ?? '') === 'entregado';
    }

    private function formatearCobro(object $p): array
    {
        $metodo = $p->metodo_pago ?? null;

        return [
            'id' => $p->id,
            'numero' => $p->numero ?? $p->id,
            'cliente' => $p->cliente_nombre ?? 'Cliente sin registrar',
            'telefono' => $p->cliente_telefono ?? null,
            'fecha' => isset($p->created_at) ? Carbon::parse($p->created_at)->format('d/m/Y H:i') : '',
            'metodo' => $metodo ? (self::METODOS[$metodo] ?? ucfirst($metodo)) : '—',
            'total' => (float) ($p->total ?? 0),
            'pagado' => $this->estaPagado($p),
            'estado_texto' => self::ESTADOS_TEXTO[$p->estado ?? 'pendiente'] ?? ucfirst((string) $p->estado),
        ];
    }
}
