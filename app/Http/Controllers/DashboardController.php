<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrmCliente;

class DashboardController extends Controller
{
    public function index()
    {
        // Contamos los clientes reales en la base de datos
        $clientesNuevos = CrmCliente::count();

        // Los demás datos están en 0 porque aún no creamos esas tablas
        $datos = [
            'ventas_hoy' => 0,
            'pedidos' => 0,
            'clientes_nuevos' => $clientesNuevos,
            'gasto_publicidad' => 0,
            'roi' => 0
        ];

        return view('admin.dashboard', compact('datos'));
    }
}