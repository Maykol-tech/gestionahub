<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\CrmCliente;
use App\Models\Servicio;
use App\Support\CrudModulos;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pagina de Servicios: agenda de citas (crear, editar, cambiar estado, eliminar) y catalogo de servicios. */
class ServicioController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $citas = Cita::query()->with('servicio:id,nombre')
            ->whereBetween('inicio', [now()->subDays(7)->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('inicio')
            ->get()
            ->map(fn (Cita $c) => $this->presentar($c))
            ->values();

        $servicios = Servicio::query()->where('activo', true)->orderBy('nombre')
            ->get(['id', 'nombre', 'precio', 'duracion_min']);

        return view('admin.modulos.servicios', [
            'titulo' => 'Servicios',
            'modulo' => 'servicios',
            'citas' => $citas,
            'servicios' => $servicios,
            'stats' => [
                'hoy' => Cita::query()->whereDate('inicio', today())->where('estado', '!=', 'cancelada')->count(),
                'pendientes' => Cita::query()->where('estado', 'pendiente')->where('inicio', '>=', now())->count(),
                'proximas' => Cita::query()->where('inicio', '>=', now())
                    ->whereNotIn('estado', ['cancelada', 'no_asistio'])->count(),
                'ingresos_mes' => (float) Cita::query()->where('estado', 'completada')
                    ->whereBetween('inicio', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('precio'),
            ],
            'cfgServicios' => CrudModulos::cliente('servicios'),
        ]);
    }

    public function guardarCita(Request $request): JsonResponse
    {
        return $this->guardar($request, null);
    }

    public function actualizarCita(Request $request, int $id): JsonResponse
    {
        return $this->guardar($request, Cita::query()->findOrFail($id));
    }

    public function estadoCita(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Cita::ESTADOS))],
        ]);

        Cita::query()->findOrFail($id)->update(['estado' => $datos['estado']]);

        return response()->json([
            'success' => true,
            'message' => 'Cita marcada como ' . strtolower(Cita::ESTADOS[$datos['estado']]) . '.',
        ]);
    }

    public function eliminarCita(int $id): JsonResponse
    {
        Cita::query()->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Cita eliminada.']);
    }

    private function guardar(Request $request, ?Cita $cita): JsonResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $servicio = Servicio::query()->findOrFail($datos['servicio_id']);
        $inicio = Carbon::parse($datos['inicio'])->second(0);
        $duracion = (int) $datos['duracion_min'];
        $fin = $inicio->copy()->addMinutes($duracion);

        if ($this->haySolape($inicio, $fin, $cita?->id)) {
            $mensaje = 'Ese horario ya está ocupado por otra cita.';

            return response()->json([
                'success' => false,
                'message' => $mensaje,
                'errors' => ['inicio' => [$mensaje]],
            ], 422);
        }

        $telefono = filled($datos['cliente_telefono'] ?? null) ? trim($datos['cliente_telefono']) : null;

        $payload = [
            'servicio_id' => $servicio->id,
            'crm_cliente_id' => $this->clientePorTelefono($telefono)?->id,
            'cliente_nombre' => trim($datos['cliente_nombre']),
            'cliente_telefono' => $telefono,
            'inicio' => $inicio,
            'duracion_min' => $duracion,
            'precio' => (float) ($datos['precio'] ?? 0),
            'notas' => $datos['notas'] ?? null,
        ];

        if ($cita) {
            $cita->update($payload);
            $mensaje = 'Cita actualizada.';
        } else {
            Cita::query()->create($payload + [
                'tenant_id' => $request->user()->tenant_id,
                'estado' => 'pendiente',
            ]);
            $mensaje = 'Cita agendada.';
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    }

    /** Hay solape si otra cita no cancelada del mismo dia ocupa parte del rango. */
    private function haySolape(Carbon $inicio, Carbon $fin, ?int $excluirId): bool
    {
        return Cita::query()
            ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
            ->where('estado', '!=', 'cancelada')
            ->whereBetween('inicio', [$inicio->copy()->startOfDay(), $inicio->copy()->endOfDay()])
            ->get()
            ->contains(fn (Cita $otra) => $otra->inicio < $fin && $otra->fin() > $inicio);
    }

    /** Vincula la cita con el cliente CRM que tenga ese telefono, si existe. */
    private function clientePorTelefono(?string $telefono): ?CrmCliente
    {
        return $telefono ? CrmCliente::query()->where('telefono', $telefono)->first() : null;
    }

    private function presentar(Cita $c): array
    {
        return [
            'id' => $c->id,
            'servicio_id' => $c->servicio_id,
            'servicio' => $c->servicio?->nombre ?? 'Sin servicio',
            'cliente' => $c->cliente_nombre,
            'telefono' => $c->cliente_telefono,
            'fecha' => $c->inicio->format('d/m/Y'),
            'hora' => $c->inicio->format('H:i'),
            'hora_fin' => $c->fin()->format('H:i'),
            'inicio_local' => $c->inicio->format('Y-m-d\TH:i'),
            'duracion_min' => (int) $c->duracion_min,
            'precio' => (float) $c->precio,
            'estado' => $c->estado,
            'notas' => $c->notas,
            'pasada' => $c->inicio->isPast() && ! in_array($c->estado, ['completada', 'cancelada', 'no_asistio'], true),
        ];
    }

    private function reglas(): array
    {
        return [
            'servicio_id' => ['required', 'integer'],
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'inicio' => ['required', 'date'],
            'duracion_min' => ['required', 'integer', 'between:5,1440'],
            'precio' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'inicio.date' => 'Indica una fecha y hora válidas.',
            'duracion_min.between' => 'La duración debe estar entre 5 y 1440 minutos.',
        ];
    }
}
