<?php

/*
|--------------------------------------------------------------------------
| Bloque 2: prospeccion IA (GEST-42/43), QR de mesas (GEST-46), cocina en vivo (GEST-47)
| y sucursales (GEST-48). Se carga desde routes/web.php.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\CocinaController;
use App\Http\Controllers\MesasController;
use App\Http\Controllers\ProspectosController;
use App\Http\Controllers\SucursalController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::post('/prospectos/buscar-ia', [ProspectosController::class, 'buscarIa'])->middleware('throttle:10,1')->name('prospectos.buscar_ia');
    Route::get('/mesas/{mesa}/qr', [MesasController::class, 'qr'])->whereNumber('mesa')->name('mesas.qr');
    Route::get('/cocina/fragmento', [CocinaController::class, 'fragmento'])->name('cocina.fragmento');
    Route::post('/sucursales/lista', [SucursalController::class, 'guardar'])->name('sucursales.guardar');
    Route::put('/sucursales/lista/{sucursal}', [SucursalController::class, 'actualizar'])->whereNumber('sucursal')->name('sucursales.actualizar');
    Route::patch('/sucursales/lista/{sucursal}/estado', [SucursalController::class, 'estado'])->whereNumber('sucursal')->name('sucursales.estado');
    Route::delete('/sucursales/lista/{sucursal}', [SucursalController::class, 'eliminar'])->whereNumber('sucursal')->name('sucursales.eliminar');
});
