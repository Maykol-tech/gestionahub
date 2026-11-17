<?php

/*
|--------------------------------------------------------------------------
| Reportes GEST-40 y GEST-41: exportacion a Excel y repartidores
|--------------------------------------------------------------------------
| Se carga desde routes/web.php con:  require __DIR__ . '/reportes.php';
| La pantalla sigue en /admin/reportes (ModulosController). El segmento "reportes"
| queda protegido por modulo.permitido igual que la pantalla (admin y cajero).
| Agregar repartidores y datos de prueba: solo administradores.
*/

use App\Http\Controllers\ReportesController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::get('/reportes/exportar', [ReportesController::class, 'exportar'])
        ->name('reportes.exportar')->middleware('throttle:20,1');

    Route::post('/reportes/repartidores', [ReportesController::class, 'guardarRepartidor'])
        ->name('reportes.repartidores.guardar')->middleware('throttle:30,1');

    Route::post('/reportes/datos-prueba', [ReportesController::class, 'datosPrueba'])
        ->name('reportes.datos-prueba')->middleware('throttle:20,1');
});
