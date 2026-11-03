<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegistroController;
use App\Http\Controllers\Api\MercadoPagoController;
use App\Http\Controllers\Api\SuperadminController;

// Ruta por defecto de Sanctum
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- RUTAS DEL PROYECTO GESTIONAHUB ---

// GEST-8: Registro de nuevos negocios (Tenants)
Route::post('/public/registro', [RegistroController::class, 'register']);

// GEST-9: Webhook para recibir notificaciones de Mercado Pago
Route::post('/pagos/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);

// GEST-10: Panel de Superadmin (Listar todos los negocios)
Route::get('/superadmin/tenants', [SuperadminController::class, 'index']);