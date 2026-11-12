<?php
namespace App\Http\Controllers;
use App\Models\Proveedor;
class ProveedoresController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        $proveedores = Proveedor::all();
        if($proveedores->isEmpty()) {
            $proveedores = collect([ (object)['id'=>1, 'razon_social'=>'Distribuidora SAC', 'ruc'=>'20123456789', 'contacto'=>'Juan Perez'], (object)['id'=>2, 'razon_social'=>'Bebidas del Sur', 'ruc'=>'20987654321', 'contacto'=>'Maria Lopez'] ]);
        }
        return view('admin.modulos.proveedores', compact('proveedores'));
    }
}