<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionModuloController;
use App\Http\Controllers\BotFlujosController;
use App\Http\Controllers\AperturaCajaController;
use App\Http\Controllers\CajaCierreController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\WhatsappInboxController;
use App\Http\Controllers\MesasController;
use App\Http\Controllers\CocinaController;
use App\Http\Controllers\PublicMesaController;
use App\Http\Controllers\CampanasController;
use App\Http\Controllers\ProspectosController;
use App\Http\Controllers\IngresosManualesController;
use App\Http\Controllers\SuscripcionAdminController;
use App\Http\Controllers\ReportesFinanzasController;
use App\Http\Controllers\PerfilNegocioController;
use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingEditorController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ListaEsperaController;
use App\Http\Controllers\ResenasController;
use App\Http\Controllers\PublicResenaController;
use App\Http\Controllers\GaleriaController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\TiendaAdminController;
use App\Http\Controllers\PersonalizacionController;
use App\Http\Controllers\VariantesController;
use App\Http\Controllers\LandingNegocioController;
use App\Http\Controllers\BotMenuController;
use App\Http\Controllers\ClientesReporteController;
use App\Http\Controllers\HorariosController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PublicCitaController;
use App\Http\Controllers\FidelizacionController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\PublicidadController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\TiendaConfigController;
use App\Http\Controllers\TiendaPublicaController;
use App\Http\Controllers\ModulosController;
use App\Http\Controllers\PedidosPanelController;
use App\Http\Controllers\ProductosPanelController;
use App\Http\Controllers\RepartidorAppController;
use App\Http\Controllers\RepartosController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Pedido desde el QR de cada mesa (publico, sin cuenta)
Route::get('/mesa/{token}', [PublicMesaController::class, 'mostrar'])
    ->where('token', '[A-Za-z0-9]{20,60}')->name('publico.mesa');
Route::post('/mesa/{token}/comanda', [PublicMesaController::class, 'ordenar'])
    ->where('token', '[A-Za-z0-9]{20,60}')->middleware('throttle:30,1')->name('publico.mesa.comanda');

// Landing SaaS de GestionaHub (publica). La raiz "/" sigue llevando al panel.
Route::get('/landing', [LandingController::class, 'inicio'])->name('landing');
Route::post('/landing/contacto', [LandingController::class, 'contacto'])->middleware('throttle:10,1')->name('landing.contacto');
Route::post('/landing/reclamaciones', [LandingController::class, 'reclamacion'])->middleware('throttle:5,1')->name('landing.reclamacion');

// Registro publico con prueba gratis de 14 dias
Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'mostrar'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'registrar'])->middleware('throttle:5,1')->name('registro.guardar');
});

// Login del panel
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'ingresar'])->name('login.ingresar')->middleware('throttle:20,1');
});
Route::post('/logout', [LoginController::class, 'salir'])->name('logout')->middleware('auth');

// App del repartidor (GEST-24): se abre con su enlace privado, sin login
Route::prefix('r/{token}')->name('repartidor.')->where(['token' => '[A-Za-z0-9]{20,64}'])
    ->middleware('throttle:240,1')->controller(RepartidorAppController::class)->group(function () {
        Route::get('/', 'app')->name('app');
        Route::get('/pedidos', 'pedidos')->name('pedidos');
        Route::post('/ubicacion', 'ubicacion')->name('ubicacion');
        Route::patch('/pedidos/{id}/estado', 'estado')->name('estado')->whereNumber('id');
    });

// Tienda publica (GEST-26): catalogo y checkout sin login
// Reserva de cita desde el portal publico del negocio (sin cuenta)
Route::get('/reservar/{slug}', [PublicCitaController::class, 'mostrar'])->name('publico.reservas')->where('slug', '[a-z0-9-]{2,60}');

// Configuracion publica de la tienda para la plantilla (TENANT_CONFIG)
Route::get('/tienda/{slug}/config', [TiendaAdminController::class, 'config'])
    ->where('slug', '[a-z0-9-]{2,60}')->middleware('throttle:60,1')->name('publico.tienda.config');

// Checkout de la tienda: capturar carrito sin pagar y marcarlo recuperado (publico, con limite)
Route::post('/tienda/{slug}/carrito-abandonado', [PagosController::class, 'capturarAbandono'])
    ->where('slug', '[a-z0-9-]{2,60}')->middleware('throttle:30,1')->name('publico.carrito');
Route::post('/tienda/{slug}/carrito-recuperado', [PagosController::class, 'marcarRecuperado'])
    ->where('slug', '[a-z0-9-]{2,60}')->middleware('throttle:30,1')->name('publico.carrito.recuperado');

// Landing de cada negocio (publica) y su formulario de contacto
Route::get('/web/{slug}', [LandingNegocioController::class, 'publica'])->name('publico.landing_negocio')->where('slug', '[a-z0-9-]{2,60}');
Route::post('/web/{slug}/contacto', [LandingNegocioController::class, 'contacto'])->name('publico.landing_negocio.contacto')
    ->where('slug', '[a-z0-9-]{2,60}')->middleware('throttle:10,1');

// Galeria publica de trabajos del negocio
Route::get('/galeria/{slug}', [GaleriaController::class, 'publica'])->name('publico.galeria')->where('slug', '[a-z0-9-]{2,60}');

// Reseñas publicas del negocio: ver y dejar una reseña
Route::get('/resenas/{slug}', [PublicResenaController::class, 'mostrar'])->name('publico.resenas')->where('slug', '[a-z0-9-]{2,60}');
Route::post('/resenas/{slug}', [PublicResenaController::class, 'enviar'])->name('publico.resenas.enviar')
    ->where('slug', '[a-z0-9-]{2,60}')->middleware('throttle:5,1');
Route::post('/reservar/{slug}', [PublicCitaController::class, 'reservar'])->name('publico.reservas.enviar')->where('slug', '[a-z0-9-]{2,60}')->middleware('throttle:20,1');

Route::get('/tienda/{slug}', [TiendaPublicaController::class, 'index'])->name('tienda.publica')
    ->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1');
Route::post('/tienda/{slug}/pedido', [TiendaPublicaController::class, 'pedido'])->name('tienda.pedido')
    ->where('slug', '[a-z0-9-]+')->middleware('throttle:20,1');
Route::get('/tienda/{slug}/pedido/{numero}', [TiendaPublicaController::class, 'gracias'])->name('tienda.gracias')
    ->where('slug', '[a-z0-9-]+')->where('numero', 'P-[0-9]+');

// Panel administrativo (Blade): requiere sesion iniciada
Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/', 'index')->name('dashboard');

        Route::get('/clientes', 'clientes')->name('clientes');
        Route::get('/clientes/lista', 'clientesLista')->name('clientes.lista');
        Route::post('/clientes', 'guardarCliente')->name('clientes.guardar');
    });

    // Pedidos: CRUD, cambio de estado y kanban (GEST-21 y 22)
    Route::controller(PedidosPanelController::class)->group(function () {
        Route::get('/pedidos', 'pedidos')->name('pedidos');
        Route::get('/pedidos/lista', 'pedidosLista')->name('pedidos.lista');
        Route::post('/pedidos', 'guardar')->name('pedidos.guardar');
        Route::get('/pedidos/{id}', 'detalle')->name('pedidos.detalle')->whereNumber('id');
        Route::put('/pedidos/{id}', 'actualizar')->name('pedidos.actualizar')->whereNumber('id');
        Route::patch('/pedidos/{id}/estado', 'mover')->name('pedidos.mover');
        Route::patch('/pedidos/{id}/cancelar', 'cancelar')->name('pedidos.cancelar');
        Route::patch('/pedidos/{id}/pagado', 'marcarPagado')->name('pedidos.pagado');
        Route::patch('/pedidos/{id}/repartidor', 'asignarRepartidor')->name('pedidos.repartidor');
    });

    // Repartos: repartidores, mapa en vivo y zonas de delivery (GEST-23, 24 y 25)
    Route::controller(RepartosController::class)->group(function () {
        Route::get('/repartos', 'repartos')->name('repartos')->defaults('modulo', 'repartos');   // antes que /{modulo}
        Route::get('/repartos/mapa', 'mapa')->name('repartos.mapa');
        Route::post('/repartidores', 'guardarRepartidor')->name('repartidores.guardar');
        Route::put('/repartidores/{id}', 'actualizarRepartidor')->name('repartidores.actualizar')->whereNumber('id');
        Route::patch('/repartidores/{id}/estado', 'estadoRepartidor')->name('repartidores.estado')->whereNumber('id');
        Route::post('/repartidores/{id}/enlace', 'nuevoEnlace')->name('repartidores.enlace')->whereNumber('id');
        Route::post('/zonas', 'guardarZona')->name('zonas.guardar');
        Route::put('/zonas/{id}', 'actualizarZona')->name('zonas.actualizar')->whereNumber('id');
        Route::delete('/zonas/{id}', 'eliminarZona')->name('zonas.eliminar')->whereNumber('id');
    });

    // Productos, categorias, variantes, galeria y stock (GEST-18, 19 y 20)
    Route::controller(ProductosPanelController::class)->group(function () {
        Route::get('/productos', 'productos')->name('productos');
        Route::get('/productos/lista', 'productosLista')->name('productos.lista');
        Route::post('/productos', 'guardarProducto')->name('productos.guardar');
        Route::post('/productos/{id}', 'actualizarProducto')->name('productos.actualizar')->whereNumber('id');
        Route::delete('/productos/{id}', 'eliminarProducto')->name('productos.eliminar')->whereNumber('id');
        Route::patch('/productos/{id}/estado', 'cambiarEstadoProducto')->name('productos.estado');
        Route::patch('/productos/{id}/stock', 'reponerStock')->name('productos.stock');

        Route::post('/productos/{id}/imagenes', 'subirImagenes')->name('productos.imagenes');
        Route::put('/productos/{id}/imagenes/orden', 'ordenarImagenes')->name('productos.imagenes.orden');
        Route::patch('/productos/{id}/imagenes/{imagen}/principal', 'principalImagen')->name('productos.imagenes.principal');
        Route::delete('/productos/{id}/imagenes/{imagen}', 'eliminarImagen')->name('productos.imagenes.eliminar');

        Route::post('/categorias', 'guardarCategoria')->name('categorias.guardar');
        Route::post('/categorias/{id}', 'actualizarCategoria')->name('categorias.actualizar');
        Route::delete('/categorias/{id}', 'eliminarCategoria')->name('categorias.eliminar');
    });

    // Fidelizacion: pagina, canje de recompensas y ajustes de puntos
    Route::get('/fidelizacion', [FidelizacionController::class, 'pagina'])->name('fidelizacion');
    Route::post('/fidelizacion/canjear', [FidelizacionController::class, 'canjear'])->name('fidelizacion.canjear');
    Route::post('/fidelizacion/ajustar', [FidelizacionController::class, 'ajustar'])->name('fidelizacion.ajustar');
    Route::post('/fidelizacion/niveles', [FidelizacionController::class, 'guardarNiveles'])->name('fidelizacion.niveles');

    // Servicios: agenda de citas (antes que /{modulo})
    Route::get('/servicios', [ServicioController::class, 'pagina'])->name('servicios');
    Route::post('/servicios/citas', [ServicioController::class, 'guardarCita'])->name('servicios.citas.guardar');
    Route::put('/servicios/citas/{id}', [ServicioController::class, 'actualizarCita'])->name('servicios.citas.actualizar')->whereNumber('id');
    Route::patch('/servicios/citas/{id}/estado', [ServicioController::class, 'estadoCita'])->name('servicios.citas.estado')->whereNumber('id');
    Route::delete('/servicios/citas/{id}', [ServicioController::class, 'eliminarCita'])->name('servicios.citas.eliminar')->whereNumber('id');

    // Produccion: insumos, recetas y lotes (antes que /{modulo})
    Route::get('/produccion', [ProduccionController::class, 'pagina'])->name('produccion');
    Route::post('/produccion/insumos', [ProduccionController::class, 'guardarInsumo'])->name('produccion.insumos.guardar');
    Route::put('/produccion/insumos/{id}', [ProduccionController::class, 'actualizarInsumo'])->name('produccion.insumos.actualizar')->whereNumber('id');
    Route::delete('/produccion/insumos/{id}', [ProduccionController::class, 'eliminarInsumo'])->name('produccion.insumos.eliminar')->whereNumber('id');
    Route::post('/produccion/recetas', [ProduccionController::class, 'guardarReceta'])->name('produccion.recetas.guardar');
    Route::put('/produccion/recetas/{id}', [ProduccionController::class, 'actualizarReceta'])->name('produccion.recetas.actualizar')->whereNumber('id');
    Route::delete('/produccion/recetas/{id}', [ProduccionController::class, 'eliminarReceta'])->name('produccion.recetas.eliminar')->whereNumber('id');
    Route::post('/produccion/lotes', [ProduccionController::class, 'guardarLote'])->name('produccion.lotes.guardar');
    Route::patch('/produccion/lotes/{id}/estado', [ProduccionController::class, 'estadoLote'])->name('produccion.lotes.estado')->whereNumber('id');
    Route::delete('/produccion/lotes/{id}', [ProduccionController::class, 'eliminarLote'])->name('produccion.lotes.eliminar')->whereNumber('id');

    // Publicidad: campanas, gastos reales e impacto (antes que /{modulo})
    Route::get('/publicidad', [PublicidadController::class, 'pagina'])->name('publicidad');
    Route::post('/publicidad/campanas', [PublicidadController::class, 'guardarCampana'])->name('publicidad.campanas.guardar');
    Route::put('/publicidad/campanas/{id}', [PublicidadController::class, 'actualizarCampana'])->name('publicidad.campanas.actualizar')->whereNumber('id');
    Route::delete('/publicidad/campanas/{id}', [PublicidadController::class, 'eliminarCampana'])->name('publicidad.campanas.eliminar')->whereNumber('id');
    Route::post('/publicidad/campanas/{id}/gastos', [PublicidadController::class, 'guardarGasto'])->name('publicidad.gastos.guardar')->whereNumber('id');
    Route::delete('/publicidad/gastos/{id}', [PublicidadController::class, 'eliminarGasto'])->name('publicidad.gastos.eliminar')->whereNumber('id');

    // Proveedores: pagina, ordenes de compra y su recepcion (antes que /{modulo})
    Route::get('/proveedores', [ProveedorController::class, 'pagina'])->name('proveedores');
    Route::post('/proveedores/ordenes', [ProveedorController::class, 'guardarOrden'])->name('proveedores.ordenes.guardar');
    Route::patch('/proveedores/ordenes/{id}/estado', [ProveedorController::class, 'estadoOrden'])->name('proveedores.ordenes.estado')->whereNumber('id');
    Route::delete('/proveedores/ordenes/{id}', [ProveedorController::class, 'eliminarOrden'])->name('proveedores.ordenes.eliminar')->whereNumber('id');

    // Bot de WhatsApp: flujos de respuesta, prueba y numero propio del negocio
    Route::get('/bot/flujos', [BotFlujosController::class, 'pagina'])->name('bot.flujos');
    Route::post('/bot/flujos', [BotFlujosController::class, 'guardar'])->name('bot.flujos.guardar');
    Route::put('/bot/flujos/{id}', [BotFlujosController::class, 'actualizar'])->name('bot.flujos.actualizar')->whereNumber('id');
    Route::patch('/bot/flujos/{id}/activo', [BotFlujosController::class, 'alternar'])->name('bot.flujos.alternar')->whereNumber('id');
    Route::delete('/bot/flujos/{id}', [BotFlujosController::class, 'eliminar'])->name('bot.flujos.eliminar')->whereNumber('id');
    Route::post('/bot/flujos/probar', [BotFlujosController::class, 'probar'])->name('bot.flujos.probar');
    Route::post('/bot/conexion', [BotFlujosController::class, 'guardarConexion'])->name('bot.conexion.guardar');

    // POS: venta rapida de mostrador
    Route::get('/pos', [PosController::class, 'pagina'])->name('pos');
    Route::post('/pos/cobrar', [PosController::class, 'cobrar'])->name('pos.cobrar');

    // Horarios de atencion (alimentan abierto o cerrado)
    Route::get('/horarios', [HorariosController::class, 'pagina'])->name('horarios');
    Route::post('/horarios', [HorariosController::class, 'guardar'])->name('horarios.guardar');

    // Clientes: exportacion CSV y ranking de clientes frecuentes
    Route::get('/clientes/exportar', [ClientesReporteController::class, 'exportar'])->name('clientes.exportar');
    Route::get('/clientes/ranking', [ClientesReporteController::class, 'ranking'])->name('clientes.ranking');

    // Caja: cierre diario y arqueo (/admin/caja/cierre)
    Route::get('/caja/cierre', [CajaCierreController::class, 'pagina'])->name('caja.cierre');
    Route::post('/caja/cierre', [CajaCierreController::class, 'cerrar'])->name('caja.cierre.guardar');
    Route::post('/caja/apertura', [AperturaCajaController::class, 'guardar'])->name('caja.apertura.guardar');

    // Comprobantes electronicos SUNAT (Nubefact): /admin/comprobantes
    Route::get('/comprobantes', [ComprobanteController::class, 'pagina'])->name('comprobantes');
    Route::post('/comprobantes/config', [ComprobanteController::class, 'guardarConfig'])->name('comprobantes.config');
    Route::post('/comprobantes/emitir/{pedido}', [ComprobanteController::class, 'emitir'])->whereNumber('pedido')->name('comprobantes.emitir');
    Route::post('/comprobantes/{comprobante}/anular', [ComprobanteController::class, 'anular'])->whereNumber('comprobante')->name('comprobantes.anular');
    Route::post('/comprobantes/{comprobante}/nota', [ComprobanteController::class, 'notaCredito'])->whereNumber('comprobante')->name('comprobantes.nota');

    // Campanas de WhatsApp (/admin/campanas) y prospectos (/admin/prospectos)
    Route::get('/campanas', [CampanasController::class, 'pagina'])->name('campanas');
    Route::post('/campanas', [CampanasController::class, 'guardar'])->name('campanas.guardar');
    Route::post('/campanas/{campana}/enviar', [CampanasController::class, 'enviarAhora'])->whereNumber('campana')->name('campanas.enviar');
    Route::post('/campanas/{campana}/cancelar', [CampanasController::class, 'cancelar'])->whereNumber('campana')->name('campanas.cancelar');
    Route::get('/prospectos', [ProspectosController::class, 'pagina'])->name('prospectos');
    Route::post('/prospectos', [ProspectosController::class, 'guardar'])->name('prospectos.guardar');
    Route::post('/prospectos/{prospecto}/estado', [ProspectosController::class, 'estado'])->whereNumber('prospecto')->name('prospectos.estado');

    // Menus numerados del bot (/admin/bot/menus)
    Route::get('/bot/menus', [BotMenuController::class, 'pagina'])->name('bot.menus');
    Route::post('/bot/menus', [BotMenuController::class, 'guardar'])->name('bot.menus.guardar');
    Route::post('/bot/menus/{menu}/alternar', [BotMenuController::class, 'alternar'])->whereNumber('menu')->name('bot.menus.alternar');

    // Landing de mi negocio (/admin/landing-negocio)
    Route::get('/landing-negocio', [LandingNegocioController::class, 'editor'])->name('landing_negocio');
    Route::post('/landing-negocio', [LandingNegocioController::class, 'guardar'])->name('landing_negocio.guardar');

    // Variantes, venta por kilo y ajuste de peso (/admin/variantes)
    Route::get('/variantes', [VariantesController::class, 'pagina'])->name('variantes');
    Route::post('/variantes/{producto}', [VariantesController::class, 'guardarVariante'])->whereNumber('producto')->name('variantes.guardar');
    Route::delete('/variantes/item/{variante}', [VariantesController::class, 'eliminarVariante'])->whereNumber('variante')->name('variantes.eliminar');
    Route::post('/variantes/{producto}/kilo', [VariantesController::class, 'guardarKilo'])->whereNumber('producto')->name('variantes.kilo');
    Route::post('/pedidos/{pedido}/peso', [VariantesController::class, 'ajustarPeso'])->whereNumber('pedido')->name('variantes.peso');

    // Personalizacion: rubro, plantilla sugerida y color de marca (/admin/personalizacion)
    Route::get('/personalizacion', [PersonalizacionController::class, 'pagina'])->name('personalizacion');
    Route::post('/personalizacion', [PersonalizacionController::class, 'guardar'])->name('personalizacion.guardar');

    // Formas de entrega (/admin/entregas) y contenido de la tienda (/admin/tienda/contenido)
    Route::get('/entregas', [TiendaAdminController::class, 'entregas'])->name('entregas');
    Route::post('/entregas', [TiendaAdminController::class, 'guardarEntrega'])->name('entregas.guardar');
    Route::post('/entregas/{entrega}/alternar', [TiendaAdminController::class, 'alternarEntrega'])->whereNumber('entrega')->name('entregas.alternar');
    Route::delete('/entregas/{entrega}', [TiendaAdminController::class, 'eliminarEntrega'])->whereNumber('entrega')->name('entregas.eliminar');
    Route::get('/tienda/contenido', [TiendaAdminController::class, 'contenido'])->name('tienda.contenido');
    Route::post('/tienda/contenido', [TiendaAdminController::class, 'guardarContenido'])->name('tienda.contenido.guardar');

    // Pagos manuales (Yape, Plin, transferencia) y carritos sin pagar (/admin/pagos)
    Route::get('/pagos', [PagosController::class, 'pagina'])->name('pagos');
    Route::post('/pagos', [PagosController::class, 'guardar'])->name('pagos.guardar');
    Route::post('/pagos/{pago}/confirmar', [PagosController::class, 'confirmar'])->whereNumber('pago')->name('pagos.confirmar');
    Route::post('/pagos/{pago}/rechazar', [PagosController::class, 'rechazar'])->whereNumber('pago')->name('pagos.rechazar');
    Route::post('/carritos/{carrito}/recordar', [PagosController::class, 'recordar'])->whereNumber('carrito')->name('carritos.recordar');

    // Galeria de trabajos (/admin/galeria)
    Route::get('/galeria', [GaleriaController::class, 'pagina'])->name('galeria');
    Route::post('/galeria', [GaleriaController::class, 'guardar'])->name('galeria.guardar');
    Route::post('/galeria/{item}/alternar', [GaleriaController::class, 'alternar'])->whereNumber('item')->name('galeria.alternar');
    Route::delete('/galeria/{item}', [GaleriaController::class, 'eliminar'])->whereNumber('item')->name('galeria.eliminar');

    // Reseñas de clientes: moderacion (/admin/resenas)
    Route::get('/resenas', [ResenasController::class, 'pagina'])->name('resenas');
    Route::post('/resenas/{resena}/estado', [ResenasController::class, 'estado'])->whereNumber('resena')->name('resenas.estado');

    // Lista de espera de citas y boletin de inactivos (/admin/espera)
    Route::get('/espera', [ListaEsperaController::class, 'pagina'])->name('espera');
    Route::post('/espera', [ListaEsperaController::class, 'guardar'])->name('espera.guardar');
    Route::post('/espera/{espera}/estado', [ListaEsperaController::class, 'estado'])->whereNumber('espera')->name('espera.estado');
    Route::post('/espera/boletin', [ListaEsperaController::class, 'boletin'])->name('espera.boletin');

    // Perfil del negocio (/admin/perfil) y avisos de la campana (/admin/notificaciones)
    Route::get('/perfil', [PerfilNegocioController::class, 'pagina'])->name('perfil');
    Route::post('/perfil', [PerfilNegocioController::class, 'guardar'])->name('perfil.guardar');
    Route::get('/notificaciones', [NotificacionesController::class, 'lista'])->name('notificaciones');

    // Reportes financieros: delivery, rentabilidad mensual y comparativo (/admin/reportes/finanzas)
    Route::get('/reportes/finanzas', [ReportesFinanzasController::class, 'pagina'])->name('reportes.finanzas');

    // Ingresos manuales (GEST-66): /admin/ingresos
    Route::get('/ingresos', [IngresosManualesController::class, 'pagina'])->name('ingresos');
    Route::post('/ingresos', [IngresosManualesController::class, 'guardar'])->name('ingresos.guardar');
    Route::delete('/ingresos/{ingreso}', [IngresosManualesController::class, 'eliminar'])->name('ingresos.eliminar')->whereNumber('ingreso');

    // Mesas, QR y panel de cocina: /admin/mesas y /admin/cocina
    Route::get('/mesas', [MesasController::class, 'pagina'])->name('mesas');
    Route::post('/mesas', [MesasController::class, 'guardar'])->name('mesas.guardar');
    Route::post('/mesas/{mesa}/cobrar', [MesasController::class, 'cobrar'])->whereNumber('mesa')->name('mesas.cobrar');
    Route::get('/cocina', [CocinaController::class, 'pagina'])->name('cocina');
    Route::post('/cocina/{comanda}/estado', [CocinaController::class, 'estado'])->whereNumber('comanda')->name('cocina.estado');

    // Bandeja de conversaciones de WhatsApp: /admin/whatsapp
    Route::get('/whatsapp', [WhatsappInboxController::class, 'pagina'])->name('whatsapp.inbox');
    Route::post('/whatsapp/responder', [WhatsappInboxController::class, 'responder'])->name('whatsapp.responder');

    // Tienda: configuracion (enlace, Yape, Plin) y cobro de la suscripcion SaaS
    Route::post('/tienda/config', [TiendaConfigController::class, 'guardar'])->name('tienda.guardar');
    Route::post('/planes/{slug}/pagar', [SuscripcionController::class, 'pagar'])->name('planes.pagar');

    // Gestion de Fidelizacion, Servicios, Proveedores, Produccion y Publicidad (CRUD)
    Route::controller(GestionModuloController::class)->prefix('gestion/{modulo}')->name('gestion.')->group(function () {
        Route::get('/lista', 'lista')->name('lista');
        Route::post('/', 'guardar')->name('guardar');
        Route::put('/{id}', 'actualizar')->name('actualizar')->whereNumber('id');
        Route::delete('/{id}', 'eliminar')->name('eliminar')->whereNumber('id');
    });

    // Modulos de Control, Presencia y Cuenta: /admin/caja, /admin/reportes, ...
    Route::controller(ModulosController::class)->group(function () {
        Route::patch('/cobros/{id}/cobrado', 'marcarCobrado')->name('cobros.cobrado');
        Route::post('/bot', 'guardarBot')->name('bot.guardar');
        Route::post('/personalizar', 'guardarPersonalizar')->name('personalizar.guardar');

        Route::get('/{modulo}', 'mostrar')->name('modulo')->whereIn('modulo', [
            'fidelizacion', 'servicios',
            'caja', 'reportes', 'cobros', 'repartos', 'produccion', 'publicidad', 'proveedores',
            'vitrina', 'tienda', 'bot', 'prospectar',
            'sucursales', 'planes', 'personalizar',
        ]);
        // Enlaces antiguos /admin/m/{modulo}
        Route::get('/m/{modulo}', fn (string $modulo) => redirect()->route('admin.modulo', $modulo, 301));
    });
});

// Editor de la landing SaaS y leads (solo superadmin)
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/landing', [LandingEditorController::class, 'pagina'])->name('landing');
    Route::post('/landing', [LandingEditorController::class, 'guardar'])->name('landing.guardar');
    Route::post('/landing/leads/{lead}/estado', [LandingEditorController::class, 'estadoLead'])->whereNumber('lead')->name('landing.lead');

    // Confirmar pagos de suscripcion reportados por Yape, Plin o transferencia (GEST-93)
    Route::get('/suscripciones', [SuscripcionAdminController::class, 'pagina'])->name('suscripciones');
    Route::post('/suscripciones/{pago}/confirmar', [SuscripcionAdminController::class, 'confirmar'])->whereNumber('pago')->name('suscripciones.confirmar');
    Route::post('/suscripciones/{pago}/rechazar', [SuscripcionAdminController::class, 'rechazar'])->whereNumber('pago')->name('suscripciones.rechazar');
});

// Superadmin: dueno de la plataforma. Solo rol superadmin, sin tenant.
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/', [SuperadminController::class, 'pagina'])->name('panel');
    Route::patch('/negocios/{id}/estado', [SuperadminController::class, 'cambiarEstado'])->name('negocios.estado')->whereNumber('id');
    Route::post('/negocios/{id}/extender', [SuperadminController::class, 'extender'])->name('negocios.extender')->whereNumber('id');
    Route::post('/planes', [SuperadminController::class, 'guardarPlan'])->name('planes.guardar');
    Route::put('/planes/{id}', [SuperadminController::class, 'actualizarPlan'])->name('planes.actualizar')->whereNumber('id');
});
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/citas', [\App\Http\Controllers\Admin\CitaController::class, 'store'])->name('citas.store');
});
