<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\CrmCliente;
use App\Models\Producto;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel administrativo (vistas Blade): Inicio, Clientes, Productos y Pedidos.
 * Mientras el panel no tenga login se trabaja con el tenant 1.
 */
class DashboardController extends Controller
{
    /** Columnas del Kanban: estado guardado => [titulo, clase del badge] */
    protected const KANBAN = [
        'pendiente' => ['Pendiente', 'b-a'],
        'en_camino' => ['En camino', 'b-g'],
        'entregado' => ['Entregado', 'b-ok'],
    ];

    protected const ESTADOS_TEXTO = [
        'pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'preparando' => 'Preparando',
        'listo' => 'Listo', 'en_camino' => 'En camino', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado',
    ];

    /** Modulos del prototipo que aun no tienen backend: [titulo, icono, grupo, descripcion, funciones] */
    protected const MODULOS = [
        'fidelizacion' => ['Fidelización', 'heart', 'Principal', 'Programa de puntos y recompensas por niveles para que tus clientes vuelvan.', ['Niveles Bronce, Plata y Oro según pedidos', 'Descuentos y beneficios automáticos', 'Recompensas enviadas por WhatsApp', 'Alertas de clientes que dejaron de comprar']],
        'servicios' => ['Servicios', 'wrench', 'Principal', 'Catálogo de servicios y agenda de citas para negocios que atienden con reserva.', ['Catálogo de servicios con precio y duración', 'Agenda diaria de citas', 'Recordatorios automáticos por WhatsApp', 'Ticket promedio e ingresos por servicio']],
        'caja' => ['Caja del día', 'card', 'Control', 'Ingresos y egresos del día con el resultado neto y el detalle por método de pago.', ['Apertura y cierre de caja', 'Registro de gastos e ingresos', 'Resumen por Yape, efectivo y tarjeta', 'Historial de cierres']],
        'reportes' => ['Reportes', 'chart', 'Control', 'Ventas, pedidos, ticket promedio y clientes únicos por periodo.', ['Comparativo semanal y mensual', 'Productos más vendidos', 'Exportación a PDF y Excel', 'Clientes nuevos vs. recurrentes']],
        'cobros' => ['Cobros y Pagos', 'clock', 'Control', 'Control de lo cobrado, lo pendiente y los pagos parciales.', ['Cobros por método de pago', 'Pedidos por cobrar', 'Pagos parciales', 'Conciliación mensual']],
        'repartos' => ['Repartos', 'truck', 'Control', 'Rutas de delivery del día con el estado de cada entrega.', ['Rutas agrupadas por zona', 'App para el repartidor', 'Total a cobrar por ruta', 'Historial de entregas']],
        'produccion' => ['Producción', 'settings', 'Control', 'Órdenes de producción, insumos críticos y mantenimiento de equipos.', ['Órdenes de producción del día', 'Insumos bajo el mínimo', 'Personal asignado', 'Mantenimiento de equipos']],
        'publicidad' => ['Publicidad', 'activity', 'Control', 'Gasto en anuncios y retorno por canal (Facebook, Instagram, TikTok).', ['Registro de gasto por canal', 'Ventas atribuidas', 'ROI por campaña', 'Comparativo mensual']],
        'proveedores' => ['Proveedores', 'home', 'Control', 'Directorio de proveedores con condiciones de pago y compras.', ['Ficha de cada proveedor', 'Condiciones y plazos de pago', 'Historial de compras', 'Recordatorio de pagos']],
        'vitrina' => ['Mi Vitrina', 'globe', 'Presencia', 'Constructor de tu página web con catálogo, delivery y pagos.', ['Constructor por secciones', 'Catálogo sincronizado con Productos', 'SEO y dominio propio', 'Popups y mailing']],
        'tienda' => ['Tienda pública', 'home', 'Presencia', 'La tienda que ven tus clientes, con carrito y pedidos directos.', ['Catálogo con ofertas', 'Carrito y checkout', 'Agendar citas', 'Reseñas de clientes']],
        'bot' => ['Bot WhatsApp', 'bot', 'Presencia', 'Atención automática por WhatsApp: menú, carta, pedidos y consultas.', ['Flujo de saludo y menú', 'Envío de la carta del día', 'Toma de pedidos', 'Derivación a un asesor']],
        'prospectar' => ['Prospectar', 'search', 'Presencia', 'Búsqueda de negocios cercanos que pueden convertirse en clientes.', ['Búsqueda por rubro y radio', 'Detección de oportunidades', 'Lista de contactos', 'Seguimiento comercial']],
        'sucursales' => ['Sucursales', 'map-pin', 'Cuenta', 'Gestión multi-local: horarios, zonas de delivery y cajeros por sede.', ['Ficha de cada sucursal', 'Horarios y zona de delivery', 'Cajero asignado', 'Pedidos por sede']],
        'planes' => ['Planes y facturación', 'briefcase', 'Cuenta', 'Tu plan actual, método de pago y comparativo de planes.', ['Plan y próximo cobro', 'Cambio de plan', 'Método de pago', 'Historial de facturas']],
        'personalizar' => ['Datos del negocio', 'pen', 'Cuenta', 'Nombre, teléfono, RUC y colores de tu panel.', ['Color principal', 'Color del menú lateral', 'Tipografía', 'Logo del negocio']],
    ];

    protected const MENSAJES = [
        'required' => 'Este campo es obligatorio.',
        'email' => 'Ingresa un correo válido.',
        'numeric' => 'Debe ser un número.',
        'integer' => 'Debe ser un número entero.',
        'min' => 'No puede ser menor a :min.',
        'max' => 'Máximo :max caracteres.',
        'imagen.max' => 'La imagen no debe pesar más de 4 MB.',
        'image' => 'El archivo debe ser una imagen.',
        'mimes' => 'Formatos permitidos: :values.',
        'in' => 'Opción no válida.',
        'exists' => 'La opción elegida no existe.',
        'unique' => 'Ya existe un registro con este valor.',
        'regex' => 'Formato no válido.',
        'lt' => 'Debe ser menor que el precio normal.',
        'telefono.unique' => 'Ya tienes un cliente con este teléfono.',
    ];

    public function __construct()
    {
        Carbon::setLocale('es');
    }

    // =================================================================
    // INICIO
    // =================================================================

    public function index(\Illuminate\Http\Request $request)
    {
        $this->compartirLayout();
        $tenantId = $this->tenantId();
        $ahora = now('America/Lima');

        // Rango de las tarjetas: hoy (vs ayer), semana (últimos 7 días vs los 7 anteriores) o mes (mes actual vs mes pasado a la misma fecha)
        $rango = in_array($request->query('rango'), ['hoy', 'semana', 'mes'], true) ? $request->query('rango') : 'hoy';
        [$desde, $antesDesde, $antesHasta] = match ($rango) {
            'semana' => [$ahora->copy()->subDays(6)->startOfDay(), $ahora->copy()->subDays(13)->startOfDay(), $ahora->copy()->subDays(7)],
            'mes' => [$ahora->copy()->startOfMonth(), $ahora->copy()->subMonthNoOverflow()->startOfMonth(), $ahora->copy()->subMonthNoOverflow()],
            default => [$ahora->copy()->startOfDay(), $ahora->copy()->subDay()->startOfDay(), $ahora->copy()->subDay()],
        };
        $textos = ['hoy' => ['hoy', 'vs ayer a esta hora'], 'semana' => ['últimos 7 días', 'vs los 7 días anteriores'], 'mes' => ['este mes', 'vs el mes pasado a la misma fecha']];

        $datos = [
            'ventas_hoy' => 0, 'ventas_variacion' => 0, 'pedidos_hoy' => 0, 'pedidos_pendientes' => 0,
            'ventas_semana' => 0, 'pedidos_semana' => 0,
            'clientes_nuevos' => CrmCliente::where('tenant_id', $tenantId)->where('created_at', '>=', $desde->copy()->utc())->count(),
            'rango' => $rango, 'rango_texto' => $textos[$rango][0], 'rango_compara' => $textos[$rango][1],
            'clientes_total' => CrmCliente::where('tenant_id', $tenantId)->count(),
            'productos_activos' => Producto::where('tenant_id', $tenantId)->where('activo', true)->count(),
            // Tarjeta "Gasto publicidad" del prototipo: lo gastado en el periodo y el retorno de las campañas
            'gasto_publicidad' => Schema::hasTable('campana_gastos')
                ? (float) DB::table('campana_gastos')->where('tenant_id', $tenantId)->where('fecha', '>=', $desde->toDateString())->sum('monto') : 0.0,
            'roi_publicidad' => null,
        ];
        if (Schema::hasTable('campanas_publicidad')) {
            $inv = (float) DB::table('campanas_publicidad')->where('tenant_id', $tenantId)->sum('inversion');
            $datos['roi_publicidad'] = $inv > 0 ? (float) DB::table('campanas_publicidad')->where('tenant_id', $tenantId)->sum('ventas_atribuidas') / $inv : null;
        }

        $ultimosPedidos = collect();

        if ($this->hayPedidos()) {
            $validos = fn () => $this->pedidosQuery()->where('pedidos.estado', '!=', 'cancelado');
            $hoy = $ahora->copy()->startOfDay()->utc();
            $ayer = $ahora->copy()->subDay()->startOfDay()->utc();
            $semana = $ahora->copy()->subDays(7)->utc();
            $colTotal = $this->columnaPedido('total') ? 'pedidos.total' : DB::raw('0');

            $datos['ventas_hoy'] = (float) $validos()->where('pedidos.created_at', '>=', $desde->copy()->utc())->sum($colTotal);
            $ventasAyer = (float) $validos()->whereBetween('pedidos.created_at', [$antesDesde->copy()->utc(), $antesHasta->copy()->utc()])->sum($colTotal);
            $datos['ventas_variacion'] = $ventasAyer > 0 ? (int) round((($datos['ventas_hoy'] - $ventasAyer) / $ventasAyer) * 100) : 0;
            $datos['pedidos_hoy'] = $validos()->where('pedidos.created_at', '>=', $desde->copy()->utc())->count();
            $datos['pedidos_pendientes'] = $this->contarPendientes();
            $datos['ventas_semana'] = (float) $validos()->where('pedidos.created_at', '>=', $semana)->sum($colTotal);
            $datos['pedidos_semana'] = $validos()->where('pedidos.created_at', '>=', $semana)->count();

            $ultimosPedidos = $this->pedidosQuery()->orderByDesc('pedidos.id')->limit(5)->get()->map(fn ($p) => $this->formatearPedido($p));
        }

        $stockBajo = Producto::where('tenant_id', $tenantId)
            ->where('activo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->limit(5)
            ->get();

        $hora = (int) $ahora->format('H');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

        return view('admin.dashboard', compact('datos', 'ultimosPedidos', 'stockBajo', 'saludo'));
    }

    // =================================================================
    // CLIENTES
    // =================================================================

    public function clientes()
    {
        $this->compartirLayout();

        return view('admin.clientes', ['clientes' => $this->listaClientes()]);
    }

    public function clientesLista()
    {
        return response()->json(['data' => $this->listaClientes()]);
    }

    public function guardarCliente(Request $request)
    {
        $tenantId = $this->tenantId();
        $datos = $this->validarDatosCliente($request, $tenantId);

        $cliente = CrmCliente::create($datos + [
            'tenant_id' => $tenantId,
            'fuente' => $datos['fuente'] ?? 'manual',
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Cliente registrado correctamente',
            'data' => $this->formatearCliente($cliente->fresh(), []),
        ], 201);
    }

    /** Editar un cliente: datos, etiquetas (VIP, mayorista...) y cumpleaños. */
    public function actualizarCliente(Request $request, int $id)
    {
        $tenantId = $this->tenantId();
        $cliente = CrmCliente::where('tenant_id', $tenantId)->findOrFail($id);
        $datos = $this->validarDatosCliente($request, $tenantId, $cliente->id);
        $cliente->update($datos + ['fuente' => $datos['fuente'] ?? $cliente->fuente]);

        return response()->json([
            'message' => 'Cliente actualizado',
            'data' => $this->formatearCliente($cliente->fresh(), []),
        ]);
    }

    /** Abre la tarjeta digital de puntos del cliente (crea su código la primera vez). */
    public function tarjetaCliente(int $id)
    {
        $cliente = CrmCliente::where('tenant_id', $this->tenantId())->findOrFail($id);

        return redirect()->route('tarjeta.cliente', $cliente->tokenTarjeta());
    }

    protected function validarDatosCliente(Request $request, int $tenantId, ?int $ignorar = null): array
    {
        // Etiquetas: limpia espacios, quita repetidas y vacías.
        $etiquetas = collect((array) $request->input('etiquetas', []))
            ->map(fn ($e) => trim(preg_replace('/\s+/', ' ', (string) $e)))
            ->filter()->unique(fn ($e) => mb_strtolower($e))->values()->all();
        $request->merge(['etiquetas' => $etiquetas]);

        $datos = $request->validate([
            'nombre'    => ['required', 'string', 'max:120'],
            'telefono'  => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{6,20}$/',
                            Rule::unique('crm_clientes', 'telefono')->where('tenant_id', $tenantId)->ignore($ignorar)],
            'email'     => ['nullable', 'email', 'max:120'],
            'dni'       => ['nullable', 'string', 'max:12', 'regex:/^[0-9]{8,12}$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'distrito'  => ['nullable', 'string', 'max:80'],
            'fuente'    => ['nullable', Rule::in(['landing', 'whatsapp', 'manual', 'referido', 'importacion', 'tienda', 'pos'])],
            'notas'     => ['nullable', 'string', 'max:1000'],
            'etiquetas' => ['array', 'max:6'],
            'etiquetas.*' => ['string', 'max:20'],
            'cumpleanos' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
        ], self::MENSAJES + [
            'etiquetas.max' => 'Usa como máximo 6 etiquetas por cliente.',
            'etiquetas.*.max' => 'Cada etiqueta puede tener hasta 20 letras.',
            'cumpleanos.before' => 'El cumpleaños debe ser una fecha pasada.',
            'cumpleanos.date' => 'Escribe una fecha de cumpleaños válida.',
        ]);

        if (! Schema::hasColumn('crm_clientes', 'etiquetas')) {
            unset($datos['etiquetas'], $datos['cumpleanos']);
        }

        return $datos;
    }

    // =================================================================
    // PRODUCTOS
    // =================================================================

    public function productos()
    {
        $this->compartirLayout();

        return view('admin.productos', [
            'productos' => $this->listaProductos(),
            'categorias' => $this->listaCategorias(),
        ]);
    }

    public function productosLista()
    {
        return response()->json(['data' => $this->listaProductos(), 'categorias' => $this->listaCategorias()]);
    }

    public function guardarProducto(Request $request)
    {
        return $this->persistirProducto($request, null);
    }

    public function actualizarProducto(Request $request, $id)
    {
        $producto = Producto::where('tenant_id', $this->tenantId())->findOrFail($id);

        return $this->persistirProducto($request, $producto);
    }

    public function cambiarEstadoProducto($id)
    {
        $producto = Producto::where('tenant_id', $this->tenantId())->findOrFail($id);
        $producto->update(['activo' => ! $producto->activo]);

        return response()->json([
            'message' => $producto->activo ? 'Producto activado' : 'Producto pausado',
            'data' => $this->formatearProducto($producto->load('categoria')),
        ]);
    }

    // =================================================================
    // PEDIDOS (Kanban)
    // =================================================================

    public function pedidos()
    {
        $this->compartirLayout();

        return view('admin.pedidos', [
            'pedidos' => $this->listaPedidos(),
            'columnas' => collect(self::KANBAN)->map(fn ($v, $k) => ['clave' => $k, 'titulo' => $v[0], 'badge' => $v[1]])->values(),
            'hayTabla' => $this->hayPedidos(),
        ]);
    }

    public function pedidosLista()
    {
        return response()->json(['data' => $this->listaPedidos()]);
    }

    public function moverPedido(Request $request, $id)
    {
        abort_unless($this->hayPedidos(), 404, 'La tabla de pedidos no existe.');

        $datos = $request->validate(['estado' => ['required', Rule::in(array_keys(self::KANBAN))]], self::MENSAJES);

        $pedido = $this->pedidosQuery()->where('pedidos.id', $id)->first();
        abort_unless($pedido, 404, 'Pedido no encontrado.');

        $cambios = ['estado' => $datos['estado'], 'updated_at' => now()];
        if ($datos['estado'] === 'entregado' && $this->columnaPedido('entregado_at')) {
            $cambios['entregado_at'] = now();
        }

        try {
            DB::table('pedidos')->where('id', $id)->where('tenant_id', $this->tenantId())->update($cambios);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Tu tabla de pedidos no acepta el estado "' . $datos['estado'] . '".'], 422);
        }

        $actualizado = $this->pedidosQuery()->where('pedidos.id', $id)->first();

        return response()->json([
            'message' => 'Pedido #' . ($actualizado->numero ?? $id) . ' movido a ' . self::KANBAN[$datos['estado']][0],
            'data' => $this->formatearPedido($actualizado),
        ]);
    }

    // =================================================================
    // MODULOS EN PREPARACION
    // =================================================================

    public function modulo(string $modulo)
    {
        abort_unless(isset(self::MODULOS[$modulo]), 404);
        $this->compartirLayout();

        [$titulo, $icono, $grupo, $descripcion, $funciones] = self::MODULOS[$modulo];

        return view('admin.modulo', compact('modulo', 'titulo', 'icono', 'grupo', 'descripcion', 'funciones'));
    }

    // =================================================================
    // AYUDANTES
    // =================================================================

    protected function tenantId(): int
    {
        return (int) (auth()->user()?->tenant_id ?? 1);
    }

    /** Datos del sidebar y la campanita, disponibles en todas las vistas. */
    protected function compartirLayout(): void
    {
        $tenantId = $this->tenantId();
        $tenant = Tenant::find($tenantId);
        $negocio = $tenant->nombre_negocio ?? 'Mi negocio';
        $notificaciones = [];

        $stockBajo = Producto::where('tenant_id', $tenantId)->where('activo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')->orderBy('stock')->limit(5)->get();

        foreach ($stockBajo as $p) {
            $notificaciones[] = [
                'titulo' => $p->stock <= 0 ? 'Sin stock: ' . $p->nombre : 'Stock bajo: ' . $p->nombre,
                'detalle' => "Quedan {$p->stock} und. (mínimo {$p->stock_minimo})",
                'icono' => 'alert', 'color' => $p->stock <= 0 ? '#DC2626' : '#D97706',
                'fondo' => $p->stock <= 0 ? '#FEE2E2' : '#FEF3C7',
                'url' => route('admin.productos') . '?editar=' . $p->id,
            ];
        }

        // GEST-71: insumos bajo el stock minimo, con acceso directo a crear la orden de compra
        if (\Illuminate\Support\Facades\Schema::hasTable('insumos')) {
            $insumosBajos = \App\Models\Insumo::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)
                ->where('stock_minimo', '>', 0)->whereColumn('stock', '<=', 'stock_minimo')->orderBy('stock')->limit(5)->get();
            foreach ($insumosBajos as $i) {
                $notificaciones[] = [
                    'titulo' => 'Insumo por reponer: ' . $i->nombre,
                    'detalle' => 'Quedan ' . rtrim(rtrim(number_format((float) $i->stock, 2), '0'), '.') . " {$i->unidad} (mínimo " . rtrim(rtrim(number_format((float) $i->stock_minimo, 2), '0'), '.') . '). Toca para crear la orden.',
                    'icono' => 'package', 'color' => '#D97706', 'fondo' => '#FEF3C7',
                    'url' => route('admin.proveedores') . '?insumo=' . $i->id,
                ];
            }
        }

        // Pagos con captura enviados por clientes de la tienda (u otros pagos manuales sin revisar)
        if (\Illuminate\Support\Facades\Schema::hasTable('pagos_manuales')) {
            $porRevisar = \App\Models\PagoManual::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('estado', 'pendiente')->count();
            if ($porRevisar > 0) {
                $notificaciones[] = [
                    'titulo' => $porRevisar === 1 ? '1 pago por revisar' : "{$porRevisar} pagos por revisar",
                    'detalle' => 'Clientes que pagaron con Yape, Plin o transferencia',
                    'icono' => 'card', 'color' => '#1D4ED8', 'fondo' => '#DBEAFE',
                    'url' => route('admin.pagos'),
                ];
            }
        }

        $pendientes = 0;
        if ($this->hayPedidos()) {
            $pendientes = $this->contarPendientes();
            if ($pendientes > 0) {
                $notificaciones[] = [
                    'titulo' => $pendientes === 1 ? '1 pedido pendiente' : "{$pendientes} pedidos pendientes",
                    'detalle' => 'Revísalos en el tablero de pedidos',
                    'icono' => 'clipboard', 'color' => '#F47B20', 'fondo' => '#FFF1E6',
                    'url' => route('admin.pedidos'),
                ];
            }
        }

        $nuevosHoy = CrmCliente::where('tenant_id', $tenantId)->where('created_at', '>=', now()->startOfDay())->count();
        if ($nuevosHoy > 0) {
            $notificaciones[] = [
                'titulo' => $nuevosHoy === 1 ? '1 cliente nuevo hoy' : "{$nuevosHoy} clientes nuevos hoy",
                'detalle' => 'Dales la bienvenida por WhatsApp',
                'icono' => 'user-plus', 'color' => '#4B7CF5', 'fondo' => '#EEF3FF',
                'url' => route('admin.clientes'),
            ];
        }

        $plan = 'Prueba gratis';
        if ($tenant && $tenant->estado === 'trial' && $tenant->trial_ends_at) {
            $dias = max(0, (int) now()->diffInDays(Carbon::parse($tenant->trial_ends_at), false));
            $plan = "Prueba gratis · {$dias} días";
        } elseif ($tenant && $tenant->estado === 'activo') {
            $plan = 'Plan activo';
        }

        view()->share('layout', [
            'negocio' => $negocio,
            'iniciales' => $this->iniciales($negocio),
            'plan' => $plan,
            'pedidos_pendientes' => $pendientes,
            'notificaciones' => $notificaciones,
            // GEST-82: módulos ocultos según el rubro o lo que eligió el negocio
            'ocultos' => $tenantId ? (array) \App\Support\AjustesNegocio::leer((int) $tenantId)['modulos_ocultos'] : [],
            'onboarding_listo' => $tenantId ? (bool) \App\Support\AjustesNegocio::leer((int) $tenantId)['onboarding_listo'] : true,
            'logo' => $tenant->logo_url ?? null,
        ]);
    }

    protected function iniciales(string $texto): string
    {
        return collect(preg_split('/\s+/', trim($texto)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'GH';
    }

    // ---------- Clientes ----------

    protected function listaClientes()
    {
        $stats = [];
        if ($this->hayPedidos() && $this->columnaPedido('cliente_id')) {
            $total = $this->columnaPedido('total') ? 'SUM(total)' : '0';
            $stats = DB::table('pedidos')
                ->selectRaw("cliente_id, COUNT(*) AS pedidos, {$total} AS total, MAX(created_at) AS ultimo")
                ->where('tenant_id', $this->tenantId())
                ->whereNotNull('cliente_id')
                ->groupBy('cliente_id')
                ->get()->keyBy('cliente_id')->all();
        }

        return CrmCliente::where('tenant_id', $this->tenantId())
            ->latest()->limit(500)->get()
            ->map(fn ($c) => $this->formatearCliente($c, $stats))
            ->values();
    }

    protected function formatearCliente(CrmCliente $c, array $stats): array
    {
        $s = $stats[$c->id] ?? null;
        $ultimo = $s && $s->ultimo ? Carbon::parse($s->ultimo) : null;

        return [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'telefono' => $c->telefono,
            'email' => $c->email,
            'distrito' => $c->distrito,
            'direccion' => $c->direccion,
            'fuente' => $c->fuente,
            'activo' => (bool) $c->activo,
            'pedidos' => (int) ($s->pedidos ?? 0),
            'total' => (float) ($s->total ?? 0),
            'ultimo' => $ultimo ? ($ultimo->isToday() ? 'Hoy' : ($ultimo->isYesterday() ? 'Ayer' : $ultimo->diffForHumans())) : null,
            'dias_sin_comprar' => $ultimo ? (int) $ultimo->diffInDays(now()) : null,
            'nuevo' => $c->created_at && $c->created_at->gte(now()->subDays(7)),
            'registrado' => $c->created_at?->diffForHumans(),
            'desde' => $c->created_at?->format('d/m/Y'),
            'dni' => $c->dni,
            'notas' => $c->notas,
            'etiquetas' => array_values((array) ($c->etiquetas ?? [])),
            'puntos' => (int) ($c->puntos_saldo ?? 0),
        ] + $this->datosCumple($c->cumpleanos ?? null);
    }

    /** Cumpleaños: fecha, texto amigable y cuántos días faltan. */
    protected function datosCumple($fecha): array
    {
        if (! $fecha) {
            return ['cumpleanos' => null, 'cumple_texto' => null, 'cumple_dias' => null, 'cumple_mes' => false];
        }
        $fecha = Carbon::parse($fecha);
        $hoy = now()->startOfDay();
        // 29 de febrero: en años no bisiestos se celebra el 28.
        $proximo = Carbon::create($hoy->year, $fecha->month, min($fecha->day, Carbon::create($hoy->year, $fecha->month, 1)->daysInMonth));
        if ($proximo->lt($hoy)) {
            $proximo = Carbon::create($hoy->year + 1, $fecha->month, min($fecha->day, Carbon::create($hoy->year + 1, $fecha->month, 1)->daysInMonth));
        }
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return [
            'cumpleanos' => $fecha->format('Y-m-d'),
            'cumple_texto' => $fecha->day . ' de ' . $meses[$fecha->month - 1],
            'cumple_dias' => (int) $hoy->diffInDays($proximo),
            'cumple_mes' => $fecha->month === $hoy->month,
        ];
    }

    // ---------- Productos ----------

    protected function listaCategorias()
    {
        return Categoria::where('tenant_id', $this->tenantId())->orderBy('nombre')->get(['id', 'nombre']);
    }

    protected function listaProductos()
    {
        return Producto::with('categoria:id,nombre')
            ->where('tenant_id', $this->tenantId())
            ->latest()->limit(500)->get()
            ->map(fn ($p) => $this->formatearProducto($p))
            ->values();
    }

    protected function formatearProducto(Producto $p): array
    {
        $estado = ! $p->activo ? 'pausado' : ($p->stock <= 0 ? 'sin_stock' : ($p->stock <= $p->stock_minimo ? 'bajo' : 'activo'));

        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'descripcion' => $p->descripcion,
            'precio' => (float) $p->precio,
            'precio_oferta' => $p->precio_oferta !== null ? (float) $p->precio_oferta : null,
            'stock' => (int) $p->stock,
            'stock_minimo' => (int) $p->stock_minimo,
            'sku' => $p->sku,
            'categoria_id' => $p->categoria_id,
            'categoria' => $p->categoria->nombre ?? null,
            'imagen_url' => $p->imagen_url,
            // Foto de ejemplo (demo) o sin foto: se marca para invitar a subir la real.
            'foto_real' => (bool) $p->imagen_url && ! str_contains((string) $p->imagen_url, '/demo/'),
            'activo' => (bool) $p->activo,
            'estado' => $estado,
        ];
    }

    protected function persistirProducto(Request $request, ?Producto $producto)
    {
        $tenantId = $this->tenantId();

        $datos = $request->validate([
            'nombre'          => ['required', 'string', 'max:150'],
            'categoria_id'    => ['nullable', Rule::exists('categorias', 'id')->where('tenant_id', $tenantId)],
            'nueva_categoria' => ['nullable', 'string', 'max:80'],
            'precio'          => ['required', 'numeric', 'min:0', 'max:999999'],
            'precio_oferta'   => ['nullable', 'numeric', 'min:0', 'lt:precio'],
            'stock'           => ['required', 'integer', 'min:0', 'max:9999999'],
            'stock_minimo'    => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'sku'             => ['nullable', 'string', 'max:60'],
            'descripcion'     => ['nullable', 'string', 'max:2000'],
            'activo'          => ['nullable', 'boolean'],
            'imagen'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], self::MENSAJES);

        // Categoria nueva escrita en el modal
        if (! empty($datos['nueva_categoria'])) {
            $nombreCat = trim($datos['nueva_categoria']);
            $categoria = Categoria::where('tenant_id', $tenantId)->where('nombre', $nombreCat)->first()
                ?? Categoria::create(['tenant_id' => $tenantId, 'nombre' => $nombreCat, 'slug' => Str::slug($nombreCat) ?: 'categoria']);
            $datos['categoria_id'] = $categoria->id;
        }

        $campos = collect($datos)->except(['nueva_categoria', 'imagen'])->all();
        $campos['stock_minimo'] = $campos['stock_minimo'] ?? 0;
        $campos['activo'] = $request->has('activo') ? $request->boolean('activo') : ($producto->activo ?? true);

        if (! $producto || $producto->nombre !== $campos['nombre']) {
            $campos['slug'] = $this->slugProducto($campos['nombre'], $producto?->id);
        }

        if ($producto) {
            $producto->update($campos);
        } else {
            $producto = Producto::create($campos + ['tenant_id' => $tenantId]);
        }

        if ($request->hasFile('imagen')) {
            $ruta = $request->file('imagen')->store("productos/{$tenantId}/{$producto->id}", 'public');
            $producto->update(['imagen_url' => Storage::disk('public')->url($ruta)]);

            // Si existe la galeria (GEST-19) tambien se registra ahi como imagen principal
            if (class_exists(\App\Models\ProductoImagen::class) && Schema::hasTable('producto_imagenes')) {
                \App\Models\ProductoImagen::where('producto_id', $producto->id)->update(['es_principal' => false]);
                \App\Models\ProductoImagen::create([
                    'tenant_id' => $tenantId, 'producto_id' => $producto->id, 'ruta' => $ruta,
                    'alt' => $producto->nombre, 'orden' => 0, 'es_principal' => true,
                ]);
            }
        }

        return response()->json([
            'message' => $request->route('id') ? 'Producto actualizado' : 'Producto creado correctamente',
            'data' => $this->formatearProducto($producto->fresh('categoria')),
            'categorias' => $this->listaCategorias(),
        ], $request->route('id') ? 200 : 201);
    }

    protected function slugProducto(string $nombre, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'producto';
        $slug = $base;
        $n = 2;

        while (Producto::withoutGlobalScopes()->where('tenant_id', $this->tenantId())->where('slug', $slug)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    // ---------- Pedidos ----------

    protected function hayPedidos(): bool
    {
        static $existe = null;

        return $existe ??= Schema::hasTable('pedidos');
    }

    protected function columnaPedido(string $columna): bool
    {
        static $columnas = null;
        $columnas ??= Schema::getColumnListing('pedidos');

        return in_array($columna, $columnas, true);
    }

    protected function pedidosQuery()
    {
        $q = DB::table('pedidos')->where('pedidos.tenant_id', $this->tenantId());

        if ($this->columnaPedido('cliente_id')) {
            $q->leftJoin('crm_clientes', 'crm_clientes.id', '=', 'pedidos.cliente_id')
              ->select('pedidos.*', 'crm_clientes.nombre as cliente_nombre', 'crm_clientes.telefono as cliente_telefono', 'crm_clientes.distrito as cliente_distrito');
        } else {
            $q->select('pedidos.*');
        }

        return $q;
    }

    protected function contarPendientes(): int
    {
        return $this->pedidosQuery()->whereNotIn('pedidos.estado', ['en_camino', 'entregado', 'cancelado'])->count();
    }

    protected function listaPedidos()
    {
        if (! $this->hayPedidos()) {
            return collect();
        }

        return $this->pedidosQuery()
            ->where('pedidos.estado', '!=', 'cancelado')
            ->where(function ($q) {
                // Entregados solo de los ultimos 7 dias para no llenar la columna
                $q->where('pedidos.estado', '!=', 'entregado')->orWhere('pedidos.updated_at', '>=', now()->subDays(7));
            })
            ->orderByDesc('pedidos.id')->limit(300)->get()
            ->map(fn ($p) => $this->formatearPedido($p))
            ->values();
    }

    protected function formatearPedido(object $p): array
    {
        $estado = $p->estado ?? 'pendiente';
        $columna = in_array($estado, ['en_camino', 'entregado'], true) ? $estado : 'pendiente';
        $badge = $estado === 'cancelado' ? 'b-r' : self::KANBAN[$columna][1];
        $creado = isset($p->created_at) ? Carbon::parse($p->created_at) : null;

        return [
            'id' => $p->id,
            'numero' => $p->numero ?? $p->id,
            'cliente' => $p->cliente_nombre ?? 'Cliente sin registrar',
            'telefono' => $p->cliente_telefono ?? null,
            'distrito' => $p->cliente_distrito ?? null,
            'direccion' => $p->direccion_entrega ?? null,
            'total' => (float) ($p->total ?? 0),
            'canal' => $p->canal ?? 'manual',
            'tipo' => $p->tipo ?? null,
            'metodo_pago' => $p->metodo_pago ?? null,
            'pagado' => (bool) ($p->pagado ?? false),
            'estado' => $estado,
            'estado_texto' => self::ESTADOS_TEXTO[$estado] ?? ucfirst($estado),
            'columna' => $columna,
            'badge' => $badge,
            'hace' => $creado?->diffForHumans(),
        ];
    }
}
