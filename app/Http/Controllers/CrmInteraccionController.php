<?php

namespace App\Http\Controllers;

use App\Models\CrmInteraccion;
use Illuminate\Http\Request;

class CrmInteraccionController extends Controller
{
    public function store(Request $request, $cliente_id)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:llamada,whatsapp,reunion,nota',
            'detalle' => 'required|string',
        ]);

        $validated['tenant_id'] = 1;
        $validated['cliente_id'] = $cliente_id;

        $interaccion = CrmInteraccion::create($validated);

        return response()->json(['success' => true, 'data' => $interaccion], 201);
    }

    public function index($cliente_id)
    {
        $interacciones = CrmInteraccion::where('cliente_id', $cliente_id)->orderBy('created_at', 'desc')->get();
        return response()->json($interacciones, 200);
    }
}
