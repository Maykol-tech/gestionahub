<?php
namespace App\Http\Controllers;
class ProduccionController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        // Datos mock para producción
        $insumos = collect([ (object)['nombre'=>'Harina', 'stock'=>50, 'min'=>20], (object)['nombre'=>'Queso', 'stock'=>10, 'min'=>15] ]);
        $recetas = collect([ (object)['nombre'=>'Pizza Pepperoni', 'costo'=>12.50, 'margen'=>45] ]);
        return view('admin.modulos.produccion', compact('insumos', 'recetas'));
    }
}