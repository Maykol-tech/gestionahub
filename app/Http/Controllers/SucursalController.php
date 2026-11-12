<?php
namespace App\Http\Controllers;
use App\Models\Sucursal;
use Illuminate\Http\Request;
class SucursalController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        $sucursales = Sucursal::orderBy('nombre')->get();
        return view('admin.sucursales', compact('sucursales'));
    }
    public function store(Request $request) {
        $tenant = auth()->user()->tenant;
        $limite = $tenant->plan ? $tenant->plan->max_sucursales : 1;
        if ($tenant->sucursales()->count() >= $limite) {
            return back()->withErrors(['limite' => 'Has alcanzado el límite de sucursales de tu plan actual.']);
        }
        $request->validate(['nombre' => 'required', 'telefono' => 'nullable', 'email' => 'nullable|email', 'direccion' => 'nullable', 'horario' => 'nullable']);
        Sucursal::create(array_merge($request->all(), ['tenant_id' => $tenant->id]));
        return back()->with('success', 'Sucursal creada.');
    }
}