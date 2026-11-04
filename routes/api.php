<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegistroController;
use App\Http\Controllers\Api\MercadoPagoController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CrmClienteController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/public/registro', [RegistroController::class, 'register']);
Route::post('/pagos/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
Route::get('/superadmin/tenants', [SuperadminController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);

// GEST-15 y GEST-16: CRUD y CSV
Route::post('/clientes/importar', [CrmClienteController::class, 'importar']); // <-- Nueva ruta CSV
Route::apiResource('clientes', CrmClienteController::class);

use App\Http\Controllers\CrmInteraccionController;
Route::get('/clientes/{id}/interacciones', [CrmInteraccionController::class, 'index']);
Route::post('/clientes/{id}/interacciones', [CrmInteraccionController::class, 'store']);
