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

        $validated['tenant_id'] = $request->user()->tenant_id;
        $cliente = CrmCliente::create($validated);

        return response()->json(['success' => true, 'data' => $cliente, 'message' => 'Cliente creado'], 201);
    }

    public function importar(Request $request)
    {
        $path = storage_path('app/clientes.csv');
        
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Archivo CSV no encontrado'], 404);
        }

        $file = fopen($path, 'r');
        fgetcsv($file); // Saltar cabeceras
        $count = 0;

        while ($row = fgetcsv($file)) {
            CrmCliente::create([
                'tenant_id' => $request->user()->tenant_id,
                'nombre' => $row[0],
                'telefono' => $row[1],
                'email' => $row[2],
                'fuente' => $row[3] ?? 'importacion',
            ]);
            $count++;
        }
        fclose($file);

        return response()->json(['success' => true, 'message' => "Se importaron $count clientes correctamente"], 200);
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