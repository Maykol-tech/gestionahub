<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Mesa;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MesasController extends Controller {
    public function index() {
        $mesas = Mesa::orderBy('numero')->get();
        return view('admin.mesas', compact('mesas'));
    }
    
    public function store(Request $request) {
        $request->validate(['numero' => 'required|string|max:10']);
        Mesa::create([
            'tenant_id' => auth()->user()->tenant_id,
            'numero' => $request->numero,
        ]);
        return back()->with('success', 'Mesa creada correctamente.');
    }

    public function update(Request $request, Mesa $mesa) {
        $request->validate(['estado' => 'required|in:libre,ocupada,por_cobrar']);
        $mesa->update(['estado' => $request->estado]);
        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(Mesa $mesa) {
        $mesa->delete();
        return back()->with('success', 'Mesa eliminada.');
    }

    public function qr(Mesa $mesa) {
        // En producción esto debería apuntar a la URL pública del menú
        $url = url('/menu/' . $mesa->token); 
        $qr = QrCode::size(200)->generate($url);
        return view('admin.mesas_qr', compact('qr', 'mesa'));
    }
}