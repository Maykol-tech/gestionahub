<?php

/*
|--------------------------------------------------------------------------
| Bloque 1: exportaciones a Excel (Laravel-Excel)
|--------------------------------------------------------------------------
| Se carga desde routes/web.php con:  require __DIR__ . '/bloque1.php';
*/

use App\Http\Controllers\ExportacionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::post('/exportar/tabla', [ExportacionController::class, 'tabla'])->name('exportar.tabla')->middleware('throttle:20,1');
    Route::get('/exportar/comprobantes', [ExportacionController::class, 'comprobantes'])->name('exportar.comprobantes')->middleware('throttle:20,1');
});

// Reemplaza al CSV anterior de clientes (/admin/clientes/exportar): ahora descarga .xlsx
Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::get('/clientes/exportar', [ExportacionController::class, 'clientes'])->name('clientes.exportar')->middleware('throttle:20,1');
});
