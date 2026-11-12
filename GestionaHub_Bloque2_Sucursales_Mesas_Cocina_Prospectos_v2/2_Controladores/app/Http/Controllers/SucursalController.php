<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de sucursales del negocio. La sede principal cuenta como una mas dentro del
 * limite de sucursales del plan (planes_saas.max_sucursales).
 */
class SucursalController extends Controller
{
    private function reglas(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'horario' => ['nullable', 'string', 'max:120'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    private function limitePlan(): int
    {
        $tenant = Tenant::find(auth()->user()->tenant_id);
        if ($tenant?->plan_id && Schema::hasTable('planes_saas')) {
            return (int) (DB::table('planes_saas')->where('id', $tenant->plan_id)->value('max_sucursales') ?? 1);
        }

        return 1;
    }

    /** Sede principal + sucursales ya creadas; no puede superar el limite del plan. */
    private function verificarLimite(): void
    {
        $usadas = 1 + Sucursal::query()->count();
        if ($usadas >= $this->limitePlan()) {
            throw ValidationException::withMessages([
                'nombre' => 'Tu plan permite ' . $this->limitePlan() . ' sucursal(es) en total. Cambia de plan para abrir más.',
            ]);
        }
    }

    private function nombreLibre(string $nombre, ?int $ignorarId = null): bool
    {
        return ! Sucursal::query()->where('nombre', $nombre)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists();
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas());
        $this->verificarLimite();

        if (! $this->nombreLibre($datos['nombre'])) {
            return back()->withErrors(['nombre' => 'Ya existe una sucursal con ese nombre.'])->withInput();
        }

        Sucursal::create($datos + ['tenant_id' => auth()->user()->tenant_id, 'activo' => $request->boolean('activo', true)]);

        return redirect()->route('admin.modulo', 'sucursales')->with('ok', 'Sucursal creada.');
    }

    public function actualizar(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $datos = $request->validate($this->reglas());

        if (! $this->nombreLibre($datos['nombre'], $sucursal->id)) {
            return back()->withErrors(['nombre' => 'Ya existe una sucursal con ese nombre.'])->withInput();
        }

        $sucursal->update($datos + ['activo' => $request->boolean('activo', $sucursal->activo)]);

        return redirect()->route('admin.modulo', 'sucursales')->with('ok', 'Sucursal actualizada.');
    }

    public function estado(Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update(['activo' => ! $sucursal->activo]);

        return redirect()->route('admin.modulo', 'sucursales')
            ->with('ok', $sucursal->activo ? 'Sucursal activada.' : 'Sucursal desactivada.');
    }

    public function eliminar(Sucursal $sucursal): RedirectResponse
    {
        $nombre = $sucursal->nombre;
        $sucursal->delete();

        return redirect()->route('admin.modulo', 'sucursales')->with('ok', "Sucursal «{$nombre}» eliminada.");
    }
}
