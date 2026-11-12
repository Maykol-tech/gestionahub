<?php

/*
|--------------------------------------------------------------------------
| Reportes GEST-40 y GEST-41: exportacion a Excel
|--------------------------------------------------------------------------
| Se carga desde routes/web.php con:  require __DIR__ . '/reportes.php';
| La pantalla sigue en /admin/reportes (ModulosController). El segmento "reportes"
| queda protegido por modulo.permitido igual que la pantalla (admin y cajero).
*/

use App\Http\Controllers\ReportesController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::get('/reportes/exportar', [ReportesController::class, 'exportar'])
        ->name('reportes.exportar')->middleware('throttle:20,1');
});
