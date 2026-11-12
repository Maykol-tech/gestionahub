<?php

/*
|--------------------------------------------------------------------------
| CRUD de repartidores (pagina /admin/repartidores)
|--------------------------------------------------------------------------
| Se carga desde routes/web.php con:  require __DIR__ . '/repartidores_crud.php';
| Las rutas usan /lista para no chocar con las de la pagina Repartos (RepartosController).
*/

use App\Http\Controllers\RepartidorController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::get('/repartidores', [RepartidorController::class, 'pagina'])->name('repartidores.crud.index');
    Route::post('/repartidores/lista', [RepartidorController::class, 'store'])->name('repartidores.crud.store')->middleware('throttle:60,1');
    Route::put('/repartidores/lista/{id}', [RepartidorController::class, 'update'])->name('repartidores.crud.update')->whereNumber('id');
    Route::patch('/repartidores/lista/{id}/estado', [RepartidorController::class, 'estado'])->name('repartidores.crud.estado')->whereNumber('id');
    Route::delete('/repartidores/lista/{id}', [RepartidorController::class, 'destroy'])->name('repartidores.crud.destroy')->whereNumber('id');
    Route::post('/repartidores/lista/{id}/asignar', [RepartidorController::class, 'asignar'])->name('repartidores.crud.asignar')->whereNumber('id');
});
