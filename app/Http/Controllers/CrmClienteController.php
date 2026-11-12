<?php
namespace App\Http\Controllers;
use App\Models\CrmCliente;
use Illuminate\Http\Request;

class CrmClienteController extends Controller {
    public function index(Request $request) {
        $query = CrmCliente::query();
        if ($request->filled('q')) {
            $query->where('nombre', 'like', '%' . $request->q . '%')->orWhere('telefono', 'like', '%' . $request->q . '%');
        }
        
        $total = CrmCliente::count();
        $activos = CrmCliente::where('estado', 'activo')->count();
        $frecuentes = CrmCliente::where('total_pedidos', '>=', 3)->count();
        $nuevos = CrmCliente::where('created_at', '>=', now()->subDays(7))->count();
        $clientes = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.clientes', compact('clientes', 'total', 'activos', 'frecuentes', 'nuevos'));
    }
}