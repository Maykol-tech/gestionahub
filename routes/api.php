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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Días 1-3
Route::post('/public/registro', [RegistroController::class, 'register']);
Route::post('/pagos/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
Route::get('/superadmin/tenants', [SuperadminController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/clientes/importar', [CrmClienteController::class, 'importar']);
Route::apiResource('clientes', CrmClienteController::class);

// GEST-17 (Interacciones)
Route::get('/clientes/{id}/interacciones', [CrmInteraccionController::class, 'index']);
Route::post('/clientes/{id}/interacciones', [CrmInteraccionController::class, 'store']);

// GEST-18 (Productos y Categorías)
Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('productos', ProductoController::class);