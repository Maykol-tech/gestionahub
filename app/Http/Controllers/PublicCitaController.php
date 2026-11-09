<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Servicio;
use App\Models\Tenant;
use App\Support\Citas;
use App\Support\Horarios;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Reserva de cita desde el portal publico del negocio (/reservar/{slug}). No requiere cuenta. */
class PublicCitaController extends Controller
{
    public function mostrar(string $slug)
    {
        $negocio = $this->negocio($slug);

        $servicios = Servicio::withoutGlobalScopes()
            ->where('tenant_id', $negocio->id)->where('activo', true)
            ->orderBy('nombre')->get(['id', 'nombre', 'precio', 'duracion_min']);

        return view('publico.reservas', [
            'negocio' => $negocio->nombre_negocio,
            'slug' => $slug,
            'servicios' => $servicios,
            'abierto' => Horarios::abiertoAhora($this->horarios($negocio)),
            'enviada' => session('reserva_enviada'),
        ]);
    }

    public function reservar(Request $request, string $slug)
    {
        $negocio = $this->negocio($slug);

        $datos = $request->validate([
            'servicio_id' => ['required', 'integer'],
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'cliente_telefono' => ['required', 'string', 'max:30'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'notas' => ['nullable', 'string', 'max:300'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'fecha.date_format' => 'Elige una fecha válida.',
            'hora.date_format' => 'Elige una hora válida.',
        ]);

        $servicio = Servicio::withoutGlobalScopes()
            ->where('tenant_id', $negocio->id)->where('activo', true)
            ->findOrFail($datos['servicio_id']);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $datos['fecha'] . ' ' . $datos['hora']);
        $fin = $inicio->copy()->addMinutes((int) $servicio->duracion_min);

        if ($inicio->isPast()) {
            throw ValidationException::withMessages(['hora' => 'Esa fecha y hora ya pasaron. Elige otro horario.']);
        }

        if (Citas::haySolape((int) $negocio->id, $inicio, $fin)) {
            throw ValidationException::withMessages(['hora' => 'Ese horario ya está ocupado. Elige otro.']);
        }

        Cita::withoutGlobalScopes()->create([
            'tenant_id' => $negocio->id,
            'servicio_id' => $servicio->id,
            'cliente_nombre' => trim($datos['cliente_nombre']),
            'cliente_telefono' => trim($datos['cliente_telefono']),
            'inicio' => $inicio,
            'duracion_min' => (int) $servicio->duracion_min,
            'precio' => (float) $servicio->precio,
            'estado' => 'pendiente',
            'origen' => 'portal',
            'notas' => $datos['notas'] ?? null,
        ]);

        return redirect()->route('publico.reservas', $slug)->with('reserva_enviada', true);
    }

    private function negocio(string $slug): Tenant
    {
        return Tenant::query()->where('slug', $slug)->where('estado', 'activo')->firstOrFail();
    }

    private function horarios(Tenant $negocio): ?array
    {
        $valor = is_string($negocio->horarios) ? json_decode($negocio->horarios, true) : $negocio->horarios;

        return is_array($valor) ? $valor : null;
    }
}
