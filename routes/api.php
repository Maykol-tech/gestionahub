<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegistroController;
use App\Http\Controllers\Api\MercadoPagoController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CrmClienteController;
use App\Http\Controllers\CrmInteraccionController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;

// ---- Publicas: no piden token ----
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/public/registro', [RegistroController::class, 'register'])->middleware('throttle:5,1');
// Mercado Pago llama a este webhook sin token; la firma se valida en el bloque de pagos
Route::post('/pagos/mercadopago/webhook', [MercadoPagoController::class, 'webhook'])->middleware('throttle:120,1');

// ---- GEST-62: API de pedidos para el sistema del negocio (token de /admin/api) ----
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant.active', 'throttle:60,1'])->controller(\App\Http\Controllers\Api\PedidosApiController::class)->group(function () {
    Route::get('/pedidos', 'index')->name('api.v1.pedidos');
    Route::post('/pedidos', 'store')->name('api.v1.pedidos.crear');
    Route::get('/pedidos/{pedido}', 'show')->name('api.v1.pedidos.ver');
    Route::patch('/pedidos/{pedido}/estado', 'estado')->name('api.v1.pedidos.estado');
});

// ---- Protegidas: token Sanctum y negocio activo ----
Route::middleware(['auth:sanctum', 'tenant.active'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // Personal del negocio
    Route::middleware('role:admin|vendedor|cajero')->group(function () {
        Route::apiResource('clientes', CrmClienteController::class);

        Route::get('/clientes/{id}/interacciones', [CrmInteraccionController::class, 'index']);
        Route::post('/clientes/{id}/interacciones', [CrmInteraccionController::class, 'store']);

        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('productos', ProductoController::class);

        Route::prefix('productos/{producto}/imagenes')->group(function () {
            Route::post('/', [ProductoController::class, 'subirImagenes']);
            Route::put('/orden', [ProductoController::class, 'ordenarImagenes']);
            Route::patch('/{imagen}/principal', [ProductoController::class, 'marcarPrincipal']);
            Route::delete('/{imagen}', [ProductoController::class, 'eliminarImagen']);
        });
    });

    Route::post('/clientes/importar', [CrmClienteController::class, 'importar'])->middleware('role:admin');
    Route::get('/superadmin/tenants', [SuperadminController::class, 'index'])->middleware('role:superadmin');
});