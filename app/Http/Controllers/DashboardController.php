<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Aquí a futuro consultaremos la tabla de pedidos.
        // Por ahora enviamos datos de prueba como pide el prototipo.
        $datos = [
            'ventas_hoy' => 342,
            'pedidos' => 8,
            'clientes_nuevos' => 3,
            'gasto_publicidad' => 80,
            'roi' => 4.3
        ];

        return view('admin.dashboard', compact('datos'));
    }
}
