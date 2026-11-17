<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MercadoPagoTienda;
use Illuminate\Http\Request;

/**
 * Aviso de Mercado Pago cuando cambia un pago de la tienda.
 * No confiamos en el contenido del aviso: con el id consultamos el pago a Mercado Pago con el token del negocio.
 */
class MercadoPagoController extends Controller
{
    public function webhook(Request $request)
    {
        $negocio = (int) $request->query('negocio');
        $tipo = $request->input('type', $request->query('type', $request->query('topic')));
        $pagoId = (string) ($request->input('data.id') ?? $request->query('data_id') ?? $request->query('id') ?? '');

        if ($negocio > 0 && $tipo === 'payment' && $pagoId !== '') {
            MercadoPagoTienda::verificar($negocio, $pagoId);
        }

        // Mercado Pago siempre espera un 200
        return response()->json(['status' => 'ok']);
    }
}
