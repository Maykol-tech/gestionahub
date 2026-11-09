<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Repartidor;
use Illuminate\Http\Request;

class RepartosController extends Controller
{
    public function repartos() 
    {
        $tenantId = 2; // El ID de tu negocio

        $enCamino = Pedido::where('tenant_id', $tenantId)->where('estado', 'en_camino')->with('cliente')->get();
        $porCobrarRuta = $enCamino->where('pagado', false)->sum('total');
        $entregadosHoy = Pedido::where('tenant_id', $tenantId)->where('estado', 'entregado')->whereDate('updated_at', today())->count();
        
        $repartidores = Repartidor::where('tenant_id', $tenantId)->get();
        $hayRepartidores = $repartidores->count() > 0;

        return view('admin.modulos.repartos', compact(
            'enCamino', 
            'porCobrarRuta', 
            'entregadosHoy', 
            'repartidores', 
            'hayRepartidores'
        ));
    }
}