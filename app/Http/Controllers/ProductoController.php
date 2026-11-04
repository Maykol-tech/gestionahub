<?php
namespace App\Http\Controllers;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index() { return response()->json(Producto::with('categoria')->get(), 200); }
    
    public function store(Request $request) {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'categoria_id' => 'nullable|exists:categorias,id'
        ]);
        $validated['tenant_id'] = 1;
        $validated['slug'] = str()->slug($validated['nombre']);
        $prod = Producto::create($validated);
        return response()->json(['success' => true, 'data' => $prod], 201);
    }
}