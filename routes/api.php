<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegistroController;
use App\Http\Controllers\Api\MercadoPagoController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CrmClienteController; // <-- Nueva ruta agregada

// Ruta por defecto de Sanctum
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- RUTAS DEL PROYECTO GESTIONAHUB ---

// GEST-8: Registro
Route::post('/public/registro', [RegistroController::class, 'register']);

// GEST-9: Mercado Pago
Route::post('/pagos/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);

// GEST-10: Superadmin
Route::get('/superadmin/tenants', [SuperadminController::class, 'index']);

// GEST-12: Login
Route::post('/login', [AuthController::class, 'login']);

// GEST-15: CRUD Clientes
Route::apiResource('clientes', CrmClienteController::class);
