<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Panel del dueno de la plataforma: negocios, estados de suscripcion y planes. Solo rol superadmin. */
class SuperadminController extends Controller
{
    public const ESTADOS = ['activo' => 'Activo', 'suspendido' => 'Suspendido', 'cancelado' => 'Cancelado'];

    public function index() { return $this->pagina(); } public function pagina()
    {
        $negocios = Tenant::query()
            ->leftJoin('planes_saas', 'planes_saas.id', '=', 'tenants.plan_id')
            ->orderByDesc('tenants.created_at')
            ->limit(500)
            ->get(['tenants.id', 'tenants.nombre_negocio', 'tenants.email', 'tenants.estado',
                'tenants.suscripcion_hasta', 'tenants.created_at', 'tenants.plan_id', 'planes_saas.nombre as plan_nombre']);

        $negocios = $negocios->map(fn ($t) => [
            'id' => $t->id,
            'nombre' => $t->nombre_negocio,
            'email' => $t->email,
            'estado' => $t->estado,
            'plan_id' => $t->plan_id,
            'plan' => $t->plan_nombre ?? 'Sin plan',
            'vence' => $t->suscripcion_hasta ? \Carbon\Carbon::parse($t->suscripcion_hasta)->format('Y-m-d') : '',
            'vence_label' => $t->suscripcion_hasta ? \Carbon\Carbon::parse($t->suscripcion_hasta)->format('d/m/Y') : 'Sin fecha',
            'alta' => \Carbon\Carbon::parse($t->created_at)->format('d/m/Y'),
        ])->values();

        $planes = DB::table('planes_saas')->orderBy('precio_mensual')->get()->map(fn ($p) => [
            'id' => $p->id,
            'slug' => $p->slug,
            'nombre' => $p->nombre,
            'precio_mensual' => (float) $p->precio_mensual,
            'max_sucursales' => (int) $p->max_sucursales,
            'activo' => (bool) $p->activo,
            'negocios' => Tenant::query()->where('plan_id', $p->id)->count(),
        ])->values();

        $ingresos = (float) Tenant::query()->where('tenants.estado', 'activo')
            ->join('planes_saas', 'planes_saas.id', '=', 'tenants.plan_id')
            ->sum('planes_saas.precio_mensual');

        return view('superadmin.panel', [
            'titulo' => 'Superadmin',
            'subtitulo' => 'Negocios, suscripciones y planes de la plataforma',
            'modulo' => 'superadmin',
            'negocios' => $negocios,
            'planes' => $planes,
            'estados' => self::ESTADOS,
            'stats' => [
                'total' => $negocios->count(),
                'activos' => $negocios->where('estado', 'activo')->count(),
                'suspendidos' => $negocios->where('estado', 'suspendido')->count(),
                'por_vencer' => $negocios->filter(fn ($n) => $n['vence'] !== '' && $n['vence'] >= now()->toDateString()
                    && $n['vence'] <= now()->addDays(7)->toDateString() && $n['estado'] === 'activo')->count(),
                'ingresos_mensuales' => $ingresos,
            ],
        ]);
    }

    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(array_keys(self::ESTADOS))]]);

        Tenant::query()->findOrFail($id)->update(['estado' => $datos['estado']]);

        return response()->json([
            'success' => true,
            'message' => 'Negocio marcado como ' . strtolower(self::ESTADOS[$datos['estado']]) . '.',
        ]);
    }

    public function extender(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'dias' => ['required', 'integer', 'between:1,365'],
            'plan_id' => ['nullable', 'integer', 'exists:planes_saas,id'],
        ], ['dias.between' => 'Los días deben estar entre 1 y 365.']);

        $negocio = Tenant::query()->findOrFail($id);

        $base = $negocio->suscripcion_hasta && $negocio->suscripcion_hasta->isFuture()
            ? $negocio->suscripcion_hasta
            : now();

        $cambios = ['suscripcion_hasta' => $base->copy()->addDays((int) $datos['dias']), 'estado' => 'activo'];

        if (! empty($datos['plan_id'])) {
            $cambios['plan_id'] = $datos['plan_id'];
        }

        $negocio->update($cambios);

        return response()->json([
            'success' => true,
            'message' => 'Suscripción extendida hasta ' . $cambios['suscripcion_hasta']->format('d/m/Y') . '.',
        ]);
    }

    public function guardarPlan(Request $request): JsonResponse
    {
        return $this->grabarPlan($request, null);
    }

    public function actualizarPlan(Request $request, int $id): JsonResponse
    {
        return $this->grabarPlan($request, $id);
    }

    private function grabarPlan(Request $request, ?int $id): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('planes_saas', 'slug')->ignore($id)],
            'precio_mensual' => ['required', 'numeric', 'min:0', 'max:99999'],
            'max_sucursales' => ['required', 'integer', 'between:1,500'],
            'activo' => ['sometimes', 'boolean'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'slug.unique' => 'Ya existe un plan con ese identificador.',
            'slug.alpha_dash' => 'El identificador solo puede tener letras, números, guiones y guiones bajos.',
        ]);

        $datos['activo'] = (bool) ($datos['activo'] ?? true);
        $datos['updated_at'] = now();

        if ($id) {
            DB::table('planes_saas')->where('id', $id)->update($datos);
            $mensaje = 'Plan actualizado.';
        } else {
            $datos['created_at'] = now();
            DB::table('planes_saas')->insert($datos);
            $mensaje = 'Plan creado.';
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    }
}
