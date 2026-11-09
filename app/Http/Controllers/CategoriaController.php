<?php
namespace App\Http\Controllers;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index() { return response()->json(Categoria::all(), 200); }
    
    public function store(Request $request) {
        $validated = $request->validate(['nombre' => 'required|string|max:80']);
        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = str()->slug($validated['nombre']);
        $cat = Categoria::create($validated);
        return response()->json(['success' => true, 'data' => $cat], 201);
    }
}