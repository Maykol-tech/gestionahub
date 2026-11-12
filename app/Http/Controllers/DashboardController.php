<?php
namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Mesa;
use App\Models\Repartidor;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        $ventasHoy = Pedido::whereDate('created_at', today())->sum('total') ?? 0;
        $pedidosHoy = Pedido::whereDate('created_at', today())->count() ?? 0;
        $clientesNuevos = CrmCliente::where('created_at', '>=', now()->subDays(7))->count() ?? 0;
        $productosActivos = Producto::where('activo', 1)->count() ?? 0;
        $ultimosPedidos = Pedido::with('cliente')->orderBy('created_at', 'desc')->take(5)->get();
        return view('admin.dashboard', compact('ventasHoy', 'pedidosHoy', 'clientesNuevos', 'productosActivos', 'ultimosPedidos'));
    }

    public function clientes(Request $request) {
        $query = CrmCliente::query();
        if ($request->filled('q')) $query->where('nombre', 'like', '%' . $request->q . '%');
        $total = CrmCliente::count();
        $activos = $total;
        $frecuentes = 0;
        $nuevos = CrmCliente::where('created_at', '>=', now()->subDays(7))->count();
        $clientes = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.clientes', compact('clientes', 'total', 'activos', 'frecuentes', 'nuevos'));
    }

    public function servicios() {
        $servicios = collect([
            (object)['nombre' => 'Corte de Cabello', 'precio' => 30, 'duracion' => 30, 'activo' => true],
            (object)['nombre' => 'Manicure y Pedicure', 'precio' => 50, 'duracion' => 60, 'activo' => true]
        ]);
        return view('admin.modulos.servicios', compact('servicios'));
    }

    public function mesas() {
        $mesas = Mesa::all();
        return view('admin.modulos.mesas', compact('mesas'));
    }

    public function repartidores() {
        $repartidores = Repartidor::all();
        return view('admin.modulos.repartidores', compact('repartidores'));
    }

    public function proveedores() {
        $proveedores = Proveedor::all();
        return view('admin.modulos.proveedores', compact('proveedores'));
    }
}