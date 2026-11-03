<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MercadoPagoController;

Route::post('/pagos/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
use App\Http\Controllers\Api\RegistroController;

Route::post('/public/registro', [RegistroController::class, 'register']);