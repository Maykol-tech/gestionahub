<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recuadros "IA" de cada modulo (como en el prototipo): una frase destacada y 2-3 consejos
 * calculados con los datos reales del negocio. Cada metodo devuelve
 * ['titulo' => ..., 'frase' => ..., 'puntos' => [...]]. Si algo falla devuelve null y la vista no muestra nada.
 */
class Recomendaciones
{
    public static function para(string $modulo): ?array
    {
        $tenant = (int) (auth()->user()->tenant_id ?? 0);
        if (! $tenant || ! method_exists(self::class, $modulo)) {
            return null;
        }
        try {
            $r = self::{$modulo}($tenant);
            $r['puntos'] = array_values(array_slice(array_filter($r['puntos'] ?? []), 0, 3));
            return $r;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private static function soles(float $n): string
    {
        return 'S/ ' . number_format($n, $n == floor($n) ? 0 : 2);
    }

    private static function t(string $tabla, int $tenant)
    {
        return DB::table($tabla)->where($tabla . '.tenant_id', $tenant);
    }

    private static function hay(string $tabla): bool
    {
        return Schema::hasTable($tabla);
    }

    // ---------------- Produccion ----------------
    private static function produccion(int $t): array
    {
        $bajos = self::t('insumos', $t)->where('activo', true)->whereColumn('stock', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0)->orderByRaw('stock / stock_minimo')->limit(3)->get();
        $enProceso = self::t('ordenes_produccion', $t)->whereIn('estado', ['pendiente', 'en_proceso'])->count();
        $porVencer = self::t('ordenes_produccion', $t)->where('estado', 'terminada')->whereNotNull('vence_at')
            ->whereBetween('vence_at', [today(), today()->addDays(3)])->count();
        $pedidosHoy = self::t('pedidos', $t)->whereIn('estado', ['nuevo', 'pendiente', 'confirmado', 'preparando', 'en_preparacion'])->count();

        $frase = $bajos->isNotEmpty()
            ? 'Tienes ' . $bajos->count() . ($bajos->count() === 1 ? ' insumo' : ' insumos') . ' por debajo del mínimo: ' . $bajos->pluck('nombre')->join(', ', ' y ') . '. Repón antes de producir.'
            : ($enProceso > 0 ? "Tienes {$enProceso} " . ($enProceso === 1 ? 'lote' : 'lotes') . ' en proceso y tu stock de insumos está en orden.' : 'Tu stock de insumos está en orden. Programa el próximo lote de producción.');

        $puntos = [];
        if ($b = $bajos->first()) {
            $faltan = max(0, (float) $b->stock_minimo - (float) $b->stock);
            $puntos[] = "Stock de {$b->nombre}: " . rtrim(rtrim(number_format((float) $b->stock, 2), '0'), '.') . " {$b->unidad} · mínimo " . rtrim(rtrim(number_format((float) $b->stock_minimo, 2), '0'), '.') . '. Pide al menos ' . rtrim(rtrim(number_format($faltan, 2), '0'), '.') . " {$b->unidad} (costo aprox. " . self::soles($faltan * (float) $b->costo_unitario) . ').';
        }
        if ($pedidosHoy > 0) {
            $puntos[] = "Hay {$pedidosHoy} " . ($pedidosHoy === 1 ? 'pedido abierto' : 'pedidos abiertos') . ' en el tablero: revisa que la producción alcance.';
        }
        if ($porVencer > 0) {
            $puntos[] = "{$porVencer} " . ($porVencer === 1 ? 'lote vence' : 'lotes vencen') . ' en los próximos 3 días. Véndelos primero o arma una promoción.';
        }
        if (! $puntos) {
            $puntos[] = 'Registra recetas con sus insumos para conocer el costo real y el margen de cada producto.';
        }
        return ['titulo' => 'IA · Alerta de producción', 'frase' => $frase, 'puntos' => $puntos];
    }

    // ---------------- Publicidad ----------------
    private static function publicidad(int $t): array
    {
        $canales = self::t('campanas_publicidad', $t)
            // Gasto real (campana_gastos), igual que la tabla de Publicidad
            ->selectRaw('canal, SUM((SELECT COALESCE(SUM(g.monto), 0) FROM campana_gastos g WHERE g.campana_id = campanas_publicidad.id)) as inv, SUM(ventas_atribuidas) as ventas, SUM(alcance) as alcance, SUM(conversiones) as conv')
            ->groupBy('canal')->get()->map(function ($c) {
                $c->roas = $c->inv > 0 ? $c->ventas / $c->inv : 0;
                return $c;
            })->filter(fn ($c) => $c->inv > 0)->sortByDesc('roas')->values();

        if ($canales->isEmpty()) {
            return ['titulo' => 'IA · Recomendación', 'frase' => 'Registra tus campañas y lo que gastas en cada una para saber qué red social te trae más ventas.', 'puntos' => [
                'Empieza con un monto pequeño (S/ 20-30) en dos redes y compara cuál convierte mejor.',
                'Anota las ventas que llegan por cada campaña para calcular el retorno (ROAS).',
            ]];
        }

        $mejor = $canales->first();
        $peor = $canales->last();
        $nombre = fn ($c) => ucfirst((string) $c->canal);
        if ($canales->count() > 1 && $mejor->roas > $peor->roas) {
            $mover = max(10, round(min($peor->inv * 0.3, 50) / 10) * 10);
            $frase = $nombre($mejor) . ' está funcionando mejor. Mueve ' . self::soles($mover) . ' de ' . $nombre($peor) . ' a ' . $nombre($mejor) . '.';
            $puntos = [
                $nombre($peor) . ': ' . number_format((int) $peor->alcance) . ' de alcance y ' . (int) $peor->conv . ' conversiones, retorno de ' . number_format($peor->roas, 1) . 'x lo invertido.',
                $nombre($mejor) . ' devuelve ' . number_format($mejor->roas, 1) . 'x lo invertido' . ($peor->roas > 0 ? ' (' . number_format($mejor->roas / $peor->roas, 1) . ' veces mejor)' : '') . '. Con ' . self::soles($mover) . ' más podrías vender ~' . self::soles($mover * $mejor->roas) . '.',
            ];
        } else {
            $frase = $nombre($mejor) . ' te devuelve ' . number_format($mejor->roas, 1) . 'x lo que inviertes.';
            $puntos = [
                $mejor->roas >= 1 ? 'Cada S/ 1 invertido regresa ' . self::soles($mejor->roas) . ' en ventas: puedes subir el presupuesto poco a poco.' : 'Aún no recuperas lo invertido: cambia el anuncio o el público antes de invertir más.',
                'Prueba una segunda red con poco presupuesto para comparar resultados.',
            ];
        }
        return ['titulo' => 'IA · Recomendación', 'frase' => $frase, 'puntos' => $puntos];
    }

    // ---------------- Repartos ----------------
    private static function repartos(int $t): array
    {
        $pendientes = self::t('pedidos', $t)->whereIn('estado', ['confirmado', 'preparando', 'en_preparacion', 'listo', 'en_camino'])
            ->where(fn ($q) => $q->where('tipo', 'delivery')->orWhereNotNull('direccion_entrega'))->get(['id', 'numero', 'estado', 'total', 'pagado', 'zona_id', 'cliente_nombre', 'programado_para', 'repartidor_id']);
        $porCobrar = (float) $pendientes->where('pagado', false)->sum('total');
        $minutos = $pendientes->count() * 25;
        $frase = $pendientes->isEmpty()
            ? 'No tienes entregas pendientes ahora. Buen momento para revisar tus zonas y costos de envío.'
            : 'Tienes ' . $pendientes->count() . ($pendientes->count() === 1 ? ' entrega pendiente' : ' entregas pendientes') . ' · Total a cobrar: ' . self::soles($porCobrar) . ' · Tiempo estimado de ruta: ' . intdiv($minutos, 60) . 'h ' . ($minutos % 60) . 'min';

        $puntos = [];
        $porZona = $pendientes->whereNotNull('zona_id')->groupBy('zona_id')->filter(fn ($g) => $g->count() > 1);
        if ($porZona->isNotEmpty() && self::hay('zonas_delivery')) {
            $zid = $porZona->sortByDesc(fn ($g) => $g->count())->keys()->first();
            $zona = DB::table('zonas_delivery')->where('id', $zid)->value('nombre') ?? 'una misma zona';
            $puntos[] = "La zona {$zona} tiene " . $porZona[$zid]->count() . ' pedidos: agrúpalos en el mismo viaje y ahorra combustible.';
        }
        $prog = $pendientes->whereNotNull('programado_para')->sortBy('programado_para')->first();
        if ($prog) {
            $puntos[] = ($prog->cliente_nombre ?: 'Pedido #' . $prog->numero) . ' pidió entrega para las ' . \Carbon\Carbon::parse($prog->programado_para)->format('H:i') . '. Ponlo primero en la ruta.';
        }
        $sinRep = $pendientes->whereNull('repartidor_id')->count();
        if ($sinRep > 0) {
            $puntos[] = "{$sinRep} " . ($sinRep === 1 ? 'pedido no tiene' : 'pedidos no tienen') . ' repartidor asignado todavía.';
        }
        if ($porCobrar > 0) {
            $puntos[] = 'Recuerda al repartidor cobrar ' . self::soles($porCobrar) . ' contra entrega y registrar el pago.';
        }
        if (! $puntos) {
            $puntos[] = 'Comparte el enlace de la app con cada repartidor para ver su ubicación en el mapa.';
        }
        return ['titulo' => 'IA · Asistente de repartos', 'frase' => $frase, 'puntos' => $puntos];
    }

    // ---------------- Servicios ----------------
    private static function servicios(int $t): array
    {
        $hoy = self::t('citas', $t)->whereDate('inicio', today())->where('estado', '!=', 'cancelada')->count();
        $top = self::t('citas', $t)->join('servicios', 'servicios.id', '=', 'citas.servicio_id')
            ->where('citas.inicio', '>=', now()->subDays(7))->where('citas.estado', '!=', 'cancelada')
            ->selectRaw('servicios.nombre, COUNT(*) as n')->groupBy('servicios.nombre')->orderByDesc('n')->first();
        $sinConfirmar = self::t('citas', $t)->where('estado', 'pendiente')->where('created_at', '<=', now()->subDays(3))->where('inicio', '>=', now())->count();
        $noAsistio = self::t('citas', $t)->where('estado', 'no_asistio')->where('inicio', '>=', now()->subDays(30))->count();
        $libre = self::t('servicios', $t)->where('activo', true)
            ->whereNotIn('id', self::t('citas', $t)->where('inicio', '>=', now()->subDays(14))->whereNotNull('servicio_id')->select('servicio_id'))
            ->value('nombre');

        $frase = $hoy > 0
            ? "Tienes {$hoy} " . ($hoy === 1 ? 'cita' : 'citas') . ' hoy' . ($top ? " · \"{$top->nombre}\" es tu servicio más solicitado esta semana" : '')
            : ($top ? "\"{$top->nombre}\" es tu servicio más solicitado esta semana. Hoy no tienes citas: buen día para promocionar." : 'Aún no hay citas esta semana. Comparte tu enlace de reservas por WhatsApp.');

        return ['titulo' => 'IA · Análisis de servicios', 'frase' => $frase, 'puntos' => [
            $sinConfirmar > 0 ? "{$sinConfirmar} " . ($sinConfirmar === 1 ? 'cita lleva' : 'citas llevan') . ' más de 3 días sin confirmar. Recuérdales por WhatsApp.' : null,
            $libre ? "El servicio \"{$libre}\" no tiene citas en 2 semanas: promociónalo hoy." : null,
            $noAsistio > 0 ? "{$noAsistio} " . ($noAsistio === 1 ? 'cliente no asistió' : 'clientes no asistieron') . ' este mes. Activa los recordatorios automáticos.' : 'Activa los recordatorios para reducir las inasistencias.',
        ]];
    }

    // ---------------- Prospectar ----------------
    private static function prospectar(int $t): array
    {
        $total = self::t('prospectos', $t)->count();
        $nuevos = self::t('prospectos', $t)->where('estado', 'nuevo')->count();
        $interesados = self::t('prospectos', $t)->where('estado', 'interesado')->count();
        $rubros = self::t('prospectos', $t)->where('estado', 'nuevo')->whereNotNull('rubro')
            ->selectRaw('rubro, COUNT(*) as n')->groupBy('rubro')->orderByDesc('n')->limit(2)->get();
        $sinTel = self::t('prospectos', $t)->where('estado', 'nuevo')->where(fn ($q) => $q->whereNull('telefono')->orWhere('telefono', ''))->count();

        $frase = $total === 0
            ? 'Busca por rubro y zona: el sistema encuentra negocios cercanos que pueden ser tus clientes.'
            : "Tienes {$nuevos} " . ($nuevos === 1 ? 'negocio' : 'negocios') . ' por contactar de ' . $total . ' encontrados.';
        $puntos = $rubros->map(fn ($r) => "{$r->n} " . ($r->n == 1 ? 'negocio' : 'negocios') . ' de ' . mb_strtolower($r->rubro) . ' sin contactar: candidatos para tu oferta.')->all();
        if ($interesados > 0) {
            $puntos[] = "{$interesados} " . ($interesados === 1 ? 'prospecto está interesado' : 'prospectos están interesados') . ': envíales tu catálogo o una promoción hoy.';
        }
        if ($sinTel > 0) {
            $puntos[] = "{$sinTel} sin teléfono: búscalos en Google Maps o redes antes de descartarlos.";
        }
        if (! $puntos) {
            $puntos = ['Empieza por un rubro que ya compra productos como los tuyos (ej. restaurantes, oficinas).', 'Marca cada prospecto como Contactado o Interesado para hacer seguimiento.'];
        }
        return ['titulo' => 'IA · Identificación de oportunidades', 'frase' => $frase, 'puntos' => $puntos];
    }

    // ---------------- Reportes ----------------
    private static function reportes(int $t): array
    {
        $rango = in_array((int) request('rango'), [7, 30, 90], true) ? (int) request('rango') : 30;
        $titulo = $rango === 7 ? 'IA · Análisis de la semana' : "IA · Análisis de {$rango} días";
        $desde = now()->subDays($rango);
        $base = self::t('pedidos', $t)->where('created_at', '>=', $desde)->where('estado', '!=', 'cancelado');
        $ventas = (float) (clone $base)->sum('total');
        $n = (clone $base)->count();
        if ($n === 0) {
            return ['titulo' => $titulo, 'frase' => "Aún no hay ventas en los últimos {$rango} días para analizar.", 'puntos' => ['Registra pedidos desde el tablero, el punto de venta o la tienda para ver el análisis automático.']];
        }
        $top = self::t('pedido_items', $t)->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->where('pedidos.created_at', '>=', $desde)->where('pedidos.estado', '!=', 'cancelado')
            ->selectRaw('pedido_items.nombre, SUM(pedido_items.subtotal) as monto')->groupBy('pedido_items.nombre')->orderByDesc('monto')->first();

        $dias = (clone $base)->get(['created_at', 'total'])->groupBy(fn ($p) => \Carbon\Carbon::parse($p->created_at)->dayOfWeekIso)
            ->map(fn ($g) => (float) $g->sum('total'));
        $nombres = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
        $ticket = $ventas / $n;

        $puntos = [];
        if ($dias->count() >= 2) {
            $alto = $dias->sortDesc()->keys()->first();
            $bajo = $dias->sort()->keys()->first();
            $puntos[] = ucfirst($nombres[$alto]) . ' concentra el ' . round($dias[$alto] / max($ventas, 1) * 100) . '% de tus ventas. Programa tus descuentos para el ' . $nombres[$bajo] . ' (tu día más bajo).';
        }
        $puntos[] = 'Tu ticket promedio es ' . self::soles(round($ticket, 2)) . '. Sumar una bebida o postre podría subirlo a ' . self::soles(round($ticket * 1.12, 1)) . '.';

        $frase = $top && $ventas > 0
            ? "\"{$top->nombre}\" es el " . round($top->monto / $ventas * 100) . '% de tus ingresos: protege ese producto y cuida su margen.'
            : 'Vendiste ' . self::soles($ventas) . " en {$n} pedidos en los últimos {$rango} días.";
        return ['titulo' => $titulo, 'frase' => $frase, 'puntos' => $puntos];
    }

    // ---------------- Fidelizacion ----------------
    private static function fidelizacion(int $t): array
    {
        $ultimos = self::t('pedidos', $t)->whereNotNull('cliente_id')->where('estado', '!=', 'cancelado')
            ->selectRaw('cliente_id, MAX(created_at) as ultimo, COUNT(*) as n')->groupBy('cliente_id');
        $inactivos = self::t('crm_clientes', $t)->joinSub($ultimos, 'u', 'u.cliente_id', '=', 'crm_clientes.id')
            ->where('u.ultimo', '<', now()->subDays(15))->orderByDesc('u.n')->limit(3)->get(['crm_clientes.nombre', 'u.n', 'u.ultimo']);
        $mejor = self::t('crm_clientes', $t)->joinSub($ultimos, 'u', 'u.cliente_id', '=', 'crm_clientes.id')
            ->where('u.ultimo', '>=', now()->subDays(15))->orderByDesc('u.n')->first(['crm_clientes.nombre', 'u.n']);

        $frase = $inactivos->isNotEmpty()
            ? $inactivos->take(2)->pluck('nombre')->join(' y ') . ($inactivos->count() > 1 ? ' llevan' : ' lleva') . ' 15+ días sin pedir: un descuento de regreso puede recuperarlos.'
            : 'Tus clientes frecuentes siguen comprando. Premia a los más fieles para mantenerlos.';
        return ['titulo' => 'IA · Recomendación de fidelización', 'frase' => $frase, 'puntos' => [
            $inactivos->isNotEmpty() ? 'Tenían ' . (int) $inactivos->first()->n . ' pedidos antes de irse. Ofréceles un cupón del 15-20% por tiempo limitado.' : null,
            $mejor ? "{$mejor->nombre} tiene la mayor frecuencia ({$mejor->n} pedidos). Agrega un detalle de regalo en su próximo pedido." : 'Activa el boletín para inactivos y los puntos por compra.',
        ]];
    }

    // ---------------- Cobros ----------------
    private static function cobros(int $t): array
    {
        $pend = self::t('pedidos', $t)->where('pagado', false)->whereNotIn('estado', ['cancelado'])
            ->orderBy('created_at')->get(['numero', 'cliente_nombre', 'total', 'created_at', 'estado']);
        $total = (float) $pend->sum('total');
        if ($pend->isEmpty()) {
            return ['titulo' => 'IA · Alerta de cobros', 'frase' => 'No tienes cobros pendientes. Todo está al día.', 'puntos' => []];
        }
        $nombres = $pend->pluck('cliente_nombre')->filter()->unique()->take(2)->join(' y ');
        $puntos = $pend->where('estado', 'entregado')->take(2)->map(fn ($p) => ($p->cliente_nombre ?: 'Cliente') . ' tiene ' . self::soles((float) $p->total) . " pendientes del pedido #{$p->numero}, que ya fue entregado.")->values()->all();
        $viejo = $pend->first();
        if (\Carbon\Carbon::parse($viejo->created_at)->lt(now()->subDay())) {
            $puntos[] = "El cobro más antiguo es del pedido #{$viejo->numero} (" . \Carbon\Carbon::parse($viejo->created_at)->diffForHumans() . ').';
        }
        return ['titulo' => 'IA · Alerta de cobros', 'frase' => 'Tienes ' . self::soles($total) . ' pendientes de cobro' . ($nombres ? " — contacta a {$nombres} hoy" : ''), 'puntos' => $puntos];
    }

    // ---------------- Inicio (resumen semanal) ----------------
    private static function dashboard(int $t): array
    {
        $base = fn ($desde, $hasta) => self::t('pedidos', $t)->where('estado', '!=', 'cancelado')->whereBetween('created_at', [$desde, $hasta]);
        $ventas = (float) $base(now()->subDays(7), now())->sum('total');
        $antes = (float) $base(now()->subDays(14), now()->subDays(7))->sum('total');
        $n = $base(now()->subDays(7), now())->count();
        if ($n === 0) {
            return ['titulo' => 'IA · Resumen semanal', 'frase' => 'Registra tus primeros pedidos para generar el resumen automático.', 'puntos' => []];
        }
        $var = $antes > 0 ? (int) round(($ventas - $antes) / $antes * 100) : null;
        $frase = 'Esta semana vendiste ' . self::soles($ventas) . ($var === null ? " en {$n} pedidos" : ' — ' . ($var >= 0 ? "un {$var}% más" : 'un ' . abs($var) . '% menos') . ' que la semana pasada');

        $top = self::t('pedido_items', $t)->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->where('pedidos.created_at', '>=', now()->subDays(7))->where('pedidos.estado', '!=', 'cancelado')
            ->selectRaw('pedido_items.nombre, COUNT(DISTINCT pedidos.id) as n, AVG(pedido_items.precio_unit) as precio')
            ->groupBy('pedido_items.nombre')->orderByDesc('n')->first();
        $inactivos = self::t('crm_clientes', $t)->joinSub(
            self::t('pedidos', $t)->whereNotNull('cliente_id')->selectRaw('cliente_id, MAX(created_at) as ultimo')->groupBy('cliente_id'),
            'u', 'u.cliente_id', '=', 'crm_clientes.id')->where('u.ultimo', '<', now()->subDays(15))->count();
        $pub = self::publicidad($t);

        return ['titulo' => 'IA · Resumen semanal', 'frase' => $frase, 'puntos' => [
            $top ? "{$top->nombre} (" . self::soles((float) $top->precio) . ") es tu producto estrella: {$top->n} " . ($top->n == 1 ? 'pedido' : 'pedidos') . '. Ponlo en primera fila de tu vitrina.' : null,
            $inactivos > 0 ? "{$inactivos} " . ($inactivos === 1 ? 'cliente no vuelve' : 'clientes no vuelven') . ' hace 15 días. Envíales un mensaje de WhatsApp con descuento de regreso.' : null,
            str_contains($pub['frase'], 'funcionando mejor') ? $pub['frase'] : null,
        ]];
    }
}
