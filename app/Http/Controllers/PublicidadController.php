<?php

namespace App\Http\Controllers;

use App\Models\CampanaGasto;
use App\Models\CampanaPublicidad;
use App\Support\Publicidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pagina de Publicidad: campanas con su impacto (ROAS, CTR, CPC, CPA) y registro de gastos. */
class PublicidadController extends DashboardController
{
    public const CANALES = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'google' => 'Google Ads',
        'whatsapp' => 'WhatsApp',
        'otro' => 'Otro',
    ];

    public const OBJETIVOS = [
        'ventas' => 'Ventas',
        'alcance' => 'Alcance',
        'trafico' => 'Tráfico',
        'mensajes' => 'Mensajes',
    ];

    public function pagina()
    {
        $this->compartirLayout();

        $campanas = CampanaPublicidad::query()->with('gastos')
            ->orderByDesc('fecha_inicio')->limit(200)->get();

        $filas = $campanas->map(fn (CampanaPublicidad $c) => $this->presentar($c))->values();

        $gastado = round((float) $filas->sum('gastado'), 2);
        $ventas = round((float) $filas->sum(fn ($f) => (float) $f['ventas_atribuidas']), 2);

        return view('admin.modulos.publicidad', [
            'titulo' => 'Publicidad',
            'modulo' => 'publicidad',
            'campanas' => $filas,
            'canales' => self::CANALES,
            'objetivos' => self::OBJETIVOS,
            'stats' => [
                'gastado' => $gastado,
                'ventas' => $ventas,
                'roas' => $gastado > 0 ? round($ventas / $gastado, 2) : null,
                'alcance' => (int) $campanas->sum('alcance'),
                'conversiones' => (int) $campanas->sum('conversiones'),
            ],
        ]);
    }

    public function guardarCampana(Request $request): JsonResponse
    {
        return $this->grabarCampana($request, null);
    }

    public function actualizarCampana(Request $request, int $id): JsonResponse
    {
        return $this->grabarCampana($request, CampanaPublicidad::query()->findOrFail($id));
    }

    public function eliminarCampana(int $id): JsonResponse
    {
        CampanaPublicidad::query()->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Campaña eliminada junto con sus gastos.']);
    }

    public function guardarGasto(Request $request, int $id): JsonResponse
    {
        $campana = CampanaPublicidad::query()->with('gastos')->findOrFail($id);

        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'concepto' => ['nullable', 'string', 'max:120'],
        ], $this->mensajes() + ['monto.gt' => 'El monto debe ser mayor a cero.']);

        $gasto = CampanaGasto::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'campana_id' => $campana->id,
            'fecha' => $datos['fecha'],
            'monto' => round((float) $datos['monto'], 2),
            'concepto' => $datos['concepto'] ?? null,
        ]);

        $total = (float) $campana->gastos->sum('monto') + (float) $gasto->monto;
        $mensaje = $total > (float) $campana->inversion
            ? 'Gasto registrado. Ya superaste el presupuesto de la campaña.'
            : 'Gasto registrado.';

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'gasto' => $this->gastoPresentado($gasto),
        ]);
    }

    public function eliminarGasto(int $id): JsonResponse
    {
        CampanaGasto::query()->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Gasto eliminado.']);
    }

    private function grabarCampana(Request $request, ?CampanaPublicidad $campana): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'canal' => ['required', Rule::in(array_keys(self::CANALES))],
            'objetivo' => ['required', Rule::in(array_keys(self::OBJETIVOS))],
            'inversion' => ['required', 'numeric', 'min:0', 'max:999999'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'alcance' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'clics' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'conversiones' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'ventas_atribuidas' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'activo' => ['sometimes', 'boolean'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], $this->mensajes());

        if (isset($datos['clics'], $datos['alcance']) && $datos['clics'] > $datos['alcance']) {
            $mensaje = 'Los clics no pueden ser más que el alcance.';

            return response()->json(['success' => false, 'message' => $mensaje, 'errors' => ['clics' => [$mensaje]]], 422);
        }

        $payload = [
            'nombre' => trim($datos['nombre']),
            'canal' => $datos['canal'],
            'objetivo' => $datos['objetivo'],
            'inversion' => (float) $datos['inversion'],
            'alcance' => $this->entero($datos['alcance'] ?? null),
            'clics' => $this->entero($datos['clics'] ?? null),
            'conversiones' => $this->entero($datos['conversiones'] ?? null),
            'ventas_atribuidas' => isset($datos['ventas_atribuidas']) ? (float) $datos['ventas_atribuidas'] : null,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'] ?? null,
            'activo' => (bool) ($datos['activo'] ?? true),
            'notas' => $datos['notas'] ?? null,
        ];

        if ($campana) {
            $campana->update($payload);
            $mensaje = 'Campaña actualizada.';
        } else {
            CampanaPublicidad::query()->create($payload + ['tenant_id' => $request->user()->tenant_id]);
            $mensaje = 'Campaña creada.';
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    }

    private function presentar(CampanaPublicidad $c): array
    {
        return [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'canal' => $c->canal,
            'canal_label' => self::CANALES[$c->canal] ?? $c->canal,
            'objetivo' => $c->objetivo,
            'objetivo_label' => self::OBJETIVOS[$c->objetivo] ?? $c->objetivo,
            'presupuesto' => (float) $c->inversion,
            'inicio' => $c->fecha_inicio->format('Y-m-d'),
            'inicio_label' => $c->fecha_inicio->format('d/m/Y'),
            'fin' => $c->fecha_fin ? $c->fecha_fin->format('Y-m-d') : '',
            'fin_label' => $c->fecha_fin ? $c->fecha_fin->format('d/m/Y') : 'Sin fecha de fin',
            'activo' => (bool) $c->activo,
            'estado' => Publicidad::estado($c),
            'alcance' => $c->alcance,
            'clics' => $c->clics,
            'conversiones' => $c->conversiones,
            'ventas_atribuidas' => $c->ventas_atribuidas !== null ? (float) $c->ventas_atribuidas : null,
            'notas' => $c->notas,
            ...Publicidad::metricas($c),
            'gastos' => $c->gastos->map(fn (CampanaGasto $g) => $this->gastoPresentado($g))->values(),
        ];
    }

    private function gastoPresentado(CampanaGasto $g): array
    {
        return [
            'id' => $g->id,
            'fecha' => $g->fecha->format('d/m/Y'),
            'monto' => (float) $g->monto,
            'concepto' => $g->concepto ?? '',
        ];
    }

    private function entero($valor): ?int
    {
        return $valor === null || $valor === '' ? null : (int) $valor;
    }

    private function mensajes(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'canal.in' => 'Elige un canal válido.',
            'objetivo.in' => 'Elige un objetivo válido.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser antes del inicio.',
        ];
    }
}
