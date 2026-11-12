<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PedidoApiController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('/pedidos', [PedidoApiController::class, 'index']);
    Route::get('/pedidos/{id}', [PedidoApiController::class, 'show']);
    Route::post('/pedidos', [PedidoApiController::class, 'store']);
    Route::patch('/pedidos/{id}/estado', [PedidoApiController::class, 'updateEstado']);
});