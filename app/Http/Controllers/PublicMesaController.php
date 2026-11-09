<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Producto;
use App\Support\Comandas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Pagina publica de la mesa (se abre al escanear el QR): el cliente pide desde su celular. */
class PublicMesaController extends Controller
{
    public function mostrar(string $token): Response
    {
        $mesa = $this->mesa($token);

        $productos = Producto::withoutGlobalScopes()->where('tenant_id', $mesa->tenant_id)
            ->where('activo', true)->where('stock', '>', 0)->orderBy('nombre')->limit(200)
            ->get(['id', 'nombre', 'precio', 'precio_oferta', 'stock']);

        return response()->view('publico.mesa', [
            'mesa' => $mesa,
            'negocio' => optional($mesa->tenant)->nombre_negocio ?? 'Tu negocio',
            'productos' => $productos,
            'enviada' => session('ok'),
        ]);
    }

    public function ordenar(Request $request, string $token): RedirectResponse
    {
        $mesa = $this->mesa($token);

        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'min:0', 'max:20'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        $solicitados = array_values(array_filter(
            $datos['items'],
            fn ($i) => (int) $i['cantidad'] > 0
        ));

        Comandas::crear($mesa, $solicitados, $datos['notas'] ?? null);

        return redirect()->route('publico.mesa', $token)->with('ok', 'Tu pedido fue enviado a cocina.');
    }

    private function mesa(string $token): Mesa
    {
        return Mesa::withoutGlobalScopes()->where('token', $token)->where('activo', true)->firstOrFail();
    }
}
