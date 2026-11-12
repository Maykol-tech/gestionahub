<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductosPanelController extends Controller
{
    // Método que exige tu ruta
    public function productos()
    {
        $productos = Producto::orderBy('nombre')->get();
        
        $totalProductos = $productos->count();
        $activos = $productos->where('activo', 1)->count();
        $stockBajo = $productos->where('stock', '<=', 5)->count();
        $valorInventario = $productos->sum(function($p) { 
            return $p->precio * $p->stock; 
        });

        return view('admin.productos', compact('productos', 'totalProductos', 'activos', 'stockBajo', 'valorInventario'));
    }

    // Alias por si alguna otra ruta llama a index()
    public function pagina() { return $this->index(); } public function index()
    {
        return $this->productos();
    }
}