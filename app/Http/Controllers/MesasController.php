<?php
namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MesasController extends Controller {
    public function index(Request $request) {
        $query = Mesa::query();
        if ($request->filled('q')) $query->where('nombre', 'like', '%' . $request->q . '%');
        if ($request->filled('zona')) $query->where('zona', $request->zona);

        $mesas = $query->orderBy('nombre')->get();
        
        // AGRUPAMOS POR ZONA
        $mesasAgrupadas = $mesas->groupBy('zona');

        $total = $mesas->count();
        $libres = $mesas->where('estado', 'libre')->count();
        $ocupadas = $mesas->where('estado', '!=', 'libre')->count();

        return view('admin.modulos.mesas', compact('mesasAgrupadas', 'total', 'libres', 'ocupadas'));
    }
    
    public function guardar(Request $request) {
        $request->validate([
            'nombre' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (Mesa::where('nombre', $value)->exists()) $fail('Esta mesa ya existe.');
            }],
            'zona' => 'required|in:Salón,Terraza,Barra',
            'capacidad' => 'required|integer|min:1|max:20'
        ]);

        Mesa::create([
            'tenant_id' => auth()->user()->tenant_id, 
            'nombre' => $request->nombre,
            'zona' => $request->zona,
            'capacidad' => $request->capacidad,
            'estado' => 'libre'
        ]);
        return back()->with('success', 'Mesa creada.');
    }

    public function destroy(Mesa $mesa) {
        $mesa->delete();
        return back()->with('success', 'Mesa eliminada.');
    }

    public function qr(Mesa $mesa) {
        $url = url('/menu/' . $mesa->token);
        $qr = QrCode::size(250)->margin(1)->generate($url);
        return response()->json(['svg' => (string) $qr]);
    }
}