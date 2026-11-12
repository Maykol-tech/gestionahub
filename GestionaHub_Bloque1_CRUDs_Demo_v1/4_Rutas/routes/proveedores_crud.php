<?php

/*
|--------------------------------------------------------------------------
| CRUD de proveedores (GEST-69): las llamadas del panel van a /admin/proveedores/lista
|--------------------------------------------------------------------------
| Se carga desde routes/web.php con:  require __DIR__ . '/proveedores_crud.php';
*/

use App\Http\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant.active', 'modulo.permitido'])->group(function () {
    Route::post('/proveedores/lista', [ProveedorController::class, 'guardarProveedor'])->name('proveedores.lista.guardar');
    Route::put('/proveedores/lista/{id}', [ProveedorController::class, 'actualizarProveedor'])->name('proveedores.lista.actualizar')->whereNumber('id');
    Route::delete('/proveedores/lista/{id}', [ProveedorController::class, 'eliminarProveedor'])->name('proveedores.lista.eliminar')->whereNumber('id');
});
