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

        // Ventas del punto de venta (vigentes, sin anuladas)
        if (\Illuminate\Support\Facades\Schema::hasTable('ventas_pos')) {
            foreach (\App\Models\VentaPos::query()->vigentes()->whereDate('created_at', $fecha->toDateString())->orderBy('created_at')->get() as $v) {
                $movimientos->push([
                    'hora' => $v->created_at->format('H:i'),
                    'concepto' => 'Venta POS ' . ($v->numero ?? '#' . $v->id),
                    'detalle' => $v->cliente_nombre ?: 'Cliente varios',
                    'metodo' => $v->metodo_pago ?? 'efectivo',
                    'metodo_texto' => self::METODOS[$v->metodo_pago ?? 'efectivo'] ?? ucfirst((string) $v->metodo_pago),
                    'tipo' => 'ingreso',
                    'monto' => (float) $v->total,
                ]);
            }
        }

        // Ingresos registrados a mano (GEST-66)
        if (\Illuminate\Support\Facades\Schema::hasTable('ingresos_manuales')) {
            foreach (\App\Models\IngresoManual::query()->whereDate('fecha', $fecha->toDateString())->orderBy('created_at')->get() as $im) {
                $movimientos->push([
                    'hora' => $im->created_at?->format('H:i') ?? '—',
                    'concepto' => $im->concepto,
                    'detalle' => 'Ingreso manual' . ($im->categoria ? ' · ' . $im->categoria : ''),
                    'metodo' => 'otro',
                    'metodo_texto' => 'Manual',
                    'tipo' => 'ingreso',
                    'monto' => (float) $im->monto,
                ]);
            }
        }

        $ingresos = $movimientos->sum('monto');
        $porMetodo = $movimientos->groupBy('metodo')->map(fn ($m, $k) => [
            'clave' => $k, 'texto' => self::METODOS[$k] ?? ucfirst($k), 'monto' => $m->sum('monto'), 'cantidad' => $m->count(),
        ])->values();

        // Egresos (GEST-66)
        $gastos = collect();
        if (\Illuminate\Support\Facades\Schema::hasColumn('gastos', 'concepto')) {
            $gastos = \App\Models\Gasto::query()->whereDate('fecha', $fecha->toDateString())->orderBy('created_at')->get()
                ->map(fn ($g) => \App\Http\Controllers\GastoController::presentar($g));
        }
        $egresos = (float) $gastos->sum('monto');
        $movimientos = $movimientos->concat($gastos)->sortBy('hora')->values();

        return [
            'gastosCategorias' => \App\Models\Gasto::CATEGORIAS,
            'gastosMetodos' => \App\Models\Gasto::METODOS,
            'cantGastos' => $gastos->count(),
            'apertura' => \App\Models\AperturaCaja::query()->whereDate('fecha', $fecha->toDateString())->first(),
            'cierre' => \App\Models\CierreCaja::query()->whereDate('fecha', $fecha->toDateString())->first(),
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

    /** Reportes GEST-40 (ventas por periodo y modulo) y GEST-41 (repartidores): ver ReportesController. */
    protected function datosReportes(Request $request): array
    {
        return app(ReportesController::class)->pantalla($request, $this->tenantId());
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
            'puedeMarcar' => $this->hayPedidos() && $this->columnaPedido('pagado'),
            'hayPedidos' => $this->hayPedidos(),
            'porPagar' => $this->cuentasPorPagar(),
            'metodosPago' => \App\Models\Gasto::METODOS,
        ];
    }

    /** Lo que el negocio debe a sus proveedores: compras a crédito recibidas y aún no pagadas. */
    protected function cuentasPorPagar(): array
    {
        if (! Schema::hasTable('ordenes_compra') || ! Schema::hasColumn('ordenes_compra', 'pagada_at')) {
            return [];
        }

        return \App\Models\OrdenCompra::query()->with('proveedor:id,nombre,telefono')
            ->where('estado', 'recibida')->whereNotNull('vence_at')->whereNull('pagada_at')
            ->orderBy('vence_at')->limit(200)->get()
            ->map(fn ($o) => $this->formatearPorPagar($o))->values()->all();
    }

    private function formatearPorPagar($o): array
    {
        $vence = Carbon::parse($o->vence_at)->startOfDay();
        $dias = (int) today()->diffInDays($vence, false);

        return [
            'id' => $o->id,
            'numero' => $o->numero ?? 'OC-' . str_pad((string) $o->id, 5, '0', STR_PAD_LEFT),
            'proveedor' => optional($o->proveedor)->nombre ?? 'Proveedor eliminado',
            'telefono' => optional($o->proveedor)->telefono,
            'recibida' => $o->recibida_at?->format('d/m/Y'),
            'vence' => $vence->format('d/m/Y'),
            'dias' => $dias,
            'total' => (float) $o->total,
        ];
    }

    /** Marca como pagada una compra a crédito y, si se pide, la registra como gasto de caja de hoy. */
    public function pagarProveedor(Request $request, int $id)
    {
        abort_unless(Schema::hasColumn('ordenes_compra', 'pagada_at'), 404);
        $datos = $request->validate([
            'metodo' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Gasto::METODOS))],
            'registrar_gasto' => ['boolean'],
        ], ['metodo.required' => 'Elige cómo pagaste.', 'metodo.in' => 'Elige cómo pagaste.']);

        $orden = \App\Models\OrdenCompra::query()->with('proveedor:id,nombre')->where('estado', 'recibida')->whereNull('pagada_at')->findOrFail($id);
        $numero = $orden->numero ?? 'OC-' . str_pad((string) $orden->id, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($orden, $datos, $numero, $request) {
            $orden->update(['pagada_at' => now(), 'pago_metodo' => $datos['metodo']]);
            if ($request->boolean('registrar_gasto') && Schema::hasTable('gastos')) {
                \App\Models\Gasto::create([
                    'tenant_id' => $this->tenantId(),
                    'concepto' => mb_substr('Pago a ' . (optional($orden->proveedor)->nombre ?? 'proveedor') . ' · ' . $numero, 0, 120),
                    'monto' => $orden->total, 'fecha' => today()->toDateString(), 'metodo' => $datos['metodo'],
                    'categoria' => 'insumos', 'registrado_por' => mb_substr((string) $request->user()->name, 0, 120),
                ]);
            }
        });

        return response()->json([
            'message' => 'Pago a ' . (optional($orden->proveedor)->nombre ?? 'proveedor') . ' registrado' . ($request->boolean('registrar_gasto') ? ' y anotado en la caja de hoy' : ''),
        ]);
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
            'sucursales' => \App\Models\Sucursal::query()->orderBy('nombre')->get(),
            'usadas' => 1 + \App\Models\Sucursal::query()->count(),
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
                    'pasarela' => ($p->metodo ?? null) ? ucfirst($p->metodo) : ($p->pasarela ? ucfirst($p->pasarela) : '—'),
                    'monto' => (float) $p->monto,
                    'estado' => $p->estado,
                    'operacion' => $p->numero_operacion ?? null,
                    'motivo' => $p->motivo_rechazo ?? null,
                ]);
        }

        return [
            'planes' => $planes->values(),
            'actual' => $actual,
            'estado' => $tenant->estado ?? 'trial',
            'diasPrueba' => $diasPrueba,
            'venceTexto' => $tenant?->trial_ends_at ? Carbon::parse($tenant->trial_ends_at)->format('d/m/Y') : null,
            'pagos' => $pagos,
            // GEST-93..95: suscripción actual y pago en revisión
            'suscripcion' => Schema::hasTable('suscripciones_saas') ? DB::table('suscripciones_saas')->where('tenant_id', $this->tenantId())->orderByDesc('id')->first() : null,
            'enRevision' => Schema::hasColumn('pagos_saas', 'metodo') ? DB::table('pagos_saas')->where('tenant_id', $this->tenantId())->where('estado', 'pendiente')->whereNotNull('numero_operacion')->exists() : false,
            'prueba' => \App\Support\Prueba::de($tenant),
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
