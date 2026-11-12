<?php

/*
|--------------------------------------------------------------------------
| Modulo A: POS y Facturacion + Seguridad (pantalla de bloqueo)
|--------------------------------------------------------------------------
| Se carga desde routes/web.php con:  require __DIR__ . '/modulo_a.php';
| Si en web.php quedaron las rutas antiguas de /pos y /comprobantes, estas las reemplazan.
*/

use App\Http\Controllers\Auth\PantallaBloqueoController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// Pantalla de bloqueo por inactividad (fuera del control de modulos: la usan todos los roles)
Route::prefix('admin/bloqueo')->name('bloqueo.')->middleware('auth')->controller(PantallaBloqueoController::class)->group(function () {
    Route::post('/bloquear', 'bloquear')->name('bloquear');
    Route::post('/actividad', 'actividad')->name('actividad')->middleware('throttle:30,1');
    Route::post('/desbloquear', 'desbloquear')->name('desbloquear')->middleware('throttle:15,1');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {

    // Punto de venta: /admin/pos
    Route::controller(PosController::class)->group(function () {
        Route::get('/pos', 'pagina')->name('pos');
        Route::post('/pos/cobrar', 'cobrar')->name('pos.cobrar')->middleware('throttle:60,1');
        Route::get('/pos/ventas', 'ventas')->name('pos.ventas');
        Route::post('/pos/ventas/{venta}/anular', 'anular')->whereNumber('venta')->name('pos.anular');
        Route::get('/pos/ventas/{venta}/ticket', 'ticket')->whereNumber('venta')->name('pos.ticket');
    });

    // Comprobantes electronicos: /admin/comprobantes
    Route::controller(ComprobanteController::class)->group(function () {
        Route::get('/comprobantes', 'pagina')->name('comprobantes');
        Route::post('/comprobantes/config', 'guardarConfig')->name('comprobantes.config');
        Route::post('/comprobantes/emitir/{pedido}', 'emitir')->whereNumber('pedido')->name('comprobantes.emitir');
        Route::post('/comprobantes/venta/{venta}', 'emitirVenta')->whereNumber('venta')->name('comprobantes.emitir_venta');
        Route::get('/comprobantes/{comprobante}', 'ver')->whereNumber('comprobante')->name('comprobantes.ver');
        Route::post('/comprobantes/{comprobante}/anular', 'anular')->whereNumber('comprobante')->name('comprobantes.anular');
        Route::post('/comprobantes/{comprobante}/nota', 'notaCredito')->whereNumber('comprobante')->name('comprobantes.nota');
    });
});
