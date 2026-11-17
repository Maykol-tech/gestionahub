<?php

namespace App\Http\Controllers;

use App\Models\CobroSuscripcion;
use App\Models\CrmCliente;
use App\Models\PlanCliente;
use App\Models\SuscripcionCliente;
use App\Support\Membresias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** GEST-57: planes que el negocio vende a sus clientes, suscriptores y cobros recurrentes. */
class MembresiasController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();
        $slug = \App\Models\Tenant::query()->whereKey($this->tenantId())->value('slug');

        $planes = PlanCliente::query()->orderByDesc('activo')->orderBy('precio')->get();
        $subs = SuscripcionCliente::query()->with(['plan', 'cliente', 'cobros' => fn ($q) => $q->latest('id')])
            ->orderByRaw("CASE estado WHEN 'vencida' THEN 0 WHEN 'pendiente' THEN 1 WHEN 'activa' THEN 2 ELSE 3 END")
            ->latest('id')->limit(500)->get();

        $lista = $subs->map(function (SuscripcionCliente $s) {
            $abierto = $s->cobros->first(fn ($c) => in_array($c->estado, ['pendiente', 'por_revisar', 'vencido'], true));

            return [
                'id' => $s->id,
                'cliente' => optional($s->cliente)->nombre ?? 'Cliente',
                'telefono' => optional($s->cliente)->telefono,
                'plan' => optional($s->plan)->nombre ?? 'Plan',
                'precio' => $s->precio,
                'periodo' => PlanCliente::PERIODOS[$s->periodicidad] ?? $s->periodicidad,
                'estado' => $s->estado,
                'desde' => $s->inicio?->format('d/m/Y'),
                'proximo' => $s->proximo_cobro?->format('d/m/Y'),
                'cobro' => $abierto ? [
                    'id' => $abierto->id, 'monto' => $abierto->monto, 'estado' => $abierto->estado,
                    'vence' => $abierto->vence->format('d/m/Y'), 'captura' => (bool) $abierto->captura,
                    'referencia' => $abierto->referencia,
                    'aviso' => $s->cliente?->telefono ? $this->wa($s->cliente->telefono, Membresias::texto($s, $abierto, $abierto->estado === 'vencido' ? 'vencido' : 'nuevo')) : null,
                ] : null,
                'enlace' => Membresias::enlace($s),
            ];
        })->values();

        $activas = $subs->where('estado', 'activa');
        $cobradoMes = (float) CobroSuscripcion::query()->where('estado', 'pagado')
            ->whereBetween('pagado_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto');

        return view('admin.membresias', [
            'planes' => $planes->map(fn ($p) => [
                'id' => $p->id, 'nombre' => $p->nombre, 'descripcion' => $p->descripcion, 'precio' => $p->precio,
                'periodicidad' => $p->periodicidad, 'beneficios' => array_values((array) $p->beneficios), 'activo' => $p->activo,
                'suscriptores' => $activas->where('plan_id', $p->id)->count(),
            ])->values(),
            'suscripciones' => $lista,
            'clientes' => CrmCliente::query()->orderBy('nombre')->limit(800)->get(['id', 'nombre', 'telefono']),
            'stats' => [
                'activas' => $activas->count(),
                'vencidas' => $subs->where('estado', 'vencida')->count(),
                'canceladas' => $subs->where('estado', 'cancelada')->count(),
                'recurrente' => round($activas->sum(fn ($s) => Membresias::mensual($s->precio, $s->periodicidad)), 2),
                'cobrado_mes' => $cobradoMes,
                'por_revisar' => CobroSuscripcion::query()->where('estado', 'por_revisar')->count(),
            ],
            'periodos' => PlanCliente::PERIODOS,
            'metodos' => \App\Models\Gasto::METODOS,
            'urlPlanes' => $slug ? route('membresia.planes', $slug) : null,
        ]);
    }

    public function guardarPlan(Request $request): JsonResponse
    {
        $plan = PlanCliente::create($this->datosPlan($request) + ['tenant_id' => $this->tenantId(), 'activo' => true]);

        return response()->json(['message' => "Plan \"{$plan->nombre}\" creado."], 201);
    }

    public function actualizarPlan(Request $request, int $id): JsonResponse
    {
        $plan = PlanCliente::query()->findOrFail($id);
        $plan->update($this->datosPlan($request) + ['activo' => $request->boolean('activo', true)]);

        return response()->json(['message' => 'Plan actualizado. Los suscriptores actuales mantienen su precio.']);
    }

    private function datosPlan(Request $request): array
    {
        $request->merge(['beneficios' => collect((array) $request->input('beneficios', []))->map(fn ($b) => trim((string) $b))->filter()->values()->all()]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0.5', 'max:99999'],
            'periodicidad' => ['required', Rule::in(array_keys(PlanCliente::PERIODOS))],
            'beneficios' => ['array', 'max:8'],
            'beneficios.*' => ['string', 'max:120'],
        ], [
            'nombre.required' => 'Ponle un nombre al plan.',
            'precio.required' => 'Escribe el precio.', 'precio.min' => 'El precio debe ser mayor a 0.',
            'beneficios.max' => 'Máximo 8 beneficios.',
        ]);
    }

    public function inscribir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'plan_id' => ['required', 'integer'],
            'cliente_id' => ['required', 'integer'],
        ], ['cliente_id.required' => 'Elige un cliente.', 'plan_id.required' => 'Elige un plan.']);
        $plan = PlanCliente::query()->findOrFail($datos['plan_id']);
        $cliente = CrmCliente::query()->findOrFail($datos['cliente_id']);
        $s = Membresias::suscribir($plan, $cliente);

        return response()->json(['message' => "{$cliente->nombre} quedó inscrito en \"{$plan->nombre}\". Registra su primer pago para activarlo.", 'id' => $s->id], 201);
    }

    public function pagarCobro(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', Rule::in(array_merge(array_keys(\App\Models\Gasto::METODOS), ['mercadopago']))],
            'referencia' => ['nullable', 'string', 'max:60'],
        ], ['metodo.required' => 'Elige cómo pagó.']);
        $cobro = CobroSuscripcion::query()->whereIn('estado', ['pendiente', 'por_revisar', 'vencido'])->findOrFail($id);
        Membresias::pagar($cobro, $datos['metodo'], $datos['referencia'] ?? null);

        return response()->json(['message' => 'Pago registrado. La suscripción está activa hasta el ' . $cobro->fresh()->hasta->format('d/m/Y') . '.']);
    }

    public function rechazarCobro(int $id): JsonResponse
    {
        $cobro = CobroSuscripcion::query()->where('estado', 'por_revisar')->findOrFail($id);
        $cobro->update(['estado' => $cobro->vence->isPast() ? 'vencido' : 'pendiente']);

        return response()->json(['message' => 'Captura rechazada. El cliente lo verá en su enlace y puede enviar otra.']);
    }

    public function captura(int $id)
    {
        $cobro = CobroSuscripcion::query()->findOrFail($id);
        abort_unless($cobro->captura && Storage::disk('local')->exists($cobro->captura), 404);

        return Storage::disk('local')->response($cobro->captura, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function cancelar(int $id): JsonResponse
    {
        Membresias::cancelar(SuscripcionCliente::query()->findOrFail($id));

        return response()->json(['message' => 'Suscripción cancelada. No se generarán más cobros.']);
    }

    public function reactivar(int $id): JsonResponse
    {
        Membresias::reactivar(SuscripcionCliente::query()->findOrFail($id));

        return response()->json(['message' => 'Suscripción reactivada con un cobro nuevo por pagar.']);
    }

    private function wa(string $telefono, string $texto): string
    {
        $n = preg_replace('/\D/', '', $telefono);

        return 'https://wa.me/' . (strlen($n) === 9 ? '51' . $n : $n) . '?text=' . rawurlencode($texto);
    }
}
