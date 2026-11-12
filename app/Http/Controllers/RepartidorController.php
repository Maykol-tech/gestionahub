<?php
namespace App\Http\Controllers;
use App\Models\Repartidor;
use Illuminate\Http\Request;
class RepartidorController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        $repartidores = Repartidor::orderBy('nombre_completo')->get();
        return view('admin.repartidores', compact('repartidores'));
    }
    public function store(Request $request) {
        $request->validate([
            'nombre_completo' => 'required|string|max:150',
            'tipo_documento' => 'required|in:DNI,Cédula,Pasaporte,RUT',
            'numero_documento' => 'required|string|max:20',
            'fecha_nacimiento' => 'required|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|unique:repartidores,email',
            'tipo_vehiculo' => 'required|in:Bicicleta,Motocicleta,Automóvil,A pie',
            'placa' => 'required_unless:tipo_vehiculo,Bicicleta,A pie',
        ]);
        Repartidor::create(array_merge($request->all(), ['tenant_id' => auth()->user()->tenant_id]));
        return back()->with('success', 'Repartidor registrado.');
    }
}