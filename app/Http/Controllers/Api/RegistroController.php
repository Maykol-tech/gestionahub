<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RegistroController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validar datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'negocio' => 'required|string|max:150',
            'email' => 'required|string|email|max:120|unique:users', // El admin se loguea con este email
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        // 2. Crear el Tenant en modo Trial (14 días)
        $tenant = Tenant::create([
            'nombre_negocio' => $request->negocio,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'estado' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(14),
            // 'plan_id' => Plan::where('slug', 'emprendedor')->first()->id, // Opcional por ahora
        ]);

        // 3. Crear el Usuario Administrador para ese Tenant
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->nombre,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Rol de administrador del negocio (Spatie)
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        return response()->json([
            'success' => true,
            'message' => 'Tenant y usuario creados correctamente. Prueba de 14 días iniciada.',
            'tenant_id' => $tenant->id
        ], 201);
    }
}