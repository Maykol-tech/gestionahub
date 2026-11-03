<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Http\Request;

class SuperadminController extends Controller
{
    public function index()
    {
        // Traemos todos los tenants con sus usuarios (dueños)
        $tenants = Tenant::with('users')->get();
        
        // Traemos todos los planes disponibles
        $planes = Plan::all();

        return response()->json([
            'success' => true,
            'message' => 'Panel de Superadmin cargado correctamente',
            'data' => [
                'total_tenants' => $tenants->count(),
                'tenants' => $tenants,
                'planes' => $planes
            ]
        ], 200);
    }
}