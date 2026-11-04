<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use Illuminate\Http\Request;

class CrmClienteController extends Controller
{
    public function index()
    {
        return response()->json(CrmCliente::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:120',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:120',
            'fuente' => 'nullable|string',
        ]);

        $validated['tenant_id'] = 1; // Forzamos el tenant 1 para que no falle

        $cliente = CrmCliente::create($validated);

        return response()->json(['success' => true, 'data' => $cliente, 'message' => 'Cliente creado'], 201);
    }

    public function show($id)
    {
        return response()->json(CrmCliente::findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $cliente = CrmCliente::findOrFail($id);
        $cliente->update($request->all());
        return response()->json(['success' => true, 'data' => $cliente], 200);
    }

    public function destroy($id)
    {
        CrmCliente::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Cliente eliminado'], 200);
    }
}
