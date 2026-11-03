<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    public function webhook(Request $request)
    {
        // 1. Recibir la notificación de Mercado Pago
        $data = $request->all();
        
        Log::info('Webhook Mercado Pago recibido: ', $data);

        // 2. Validar que sea un pago
        if (isset($data['type']) && $data['type'] === 'payment') {
            $paymentId = $data['data']['id'];

            // Aquí iría la lógica real de consultar a Mercado Pago usando el SDK
            // $payment = MercadoPago\Payment::find_by_id($paymentId);

            // 3. Simulamos la actualización en la base de datos
            // (En producción, buscaríamos el pago por el ID de referencia)
        }

        // Mercado Pago siempre espera un 200 OK
        return response()->json(['status' => 'success'], 200);
    }
}