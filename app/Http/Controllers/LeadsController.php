<?php

namespace App\Http\Controllers;

use App\Models\LandingLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** GEST-90: bandeja de leads de la plataforma (superadmin) con embudo Nuevo → Contactado → Demo → Ganado / Perdido. */
class LeadsController extends Controller
{
    private function consulta(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return LandingLead::query()
            ->when($request->query('fuente'), fn ($w, $f) => $w->where('fuente', $f))
            ->when($request->query('campana'), fn ($w, $c) => $w->where('utm_campaign', $c))
            ->when($request->query('desde'), fn ($w, $d) => $w->whereDate('created_at', '>=', $d))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")->orWhere('telefono', 'like', "%{$q}%")))
            ->orderByDesc('id');
    }

    private function fila(LandingLead $l): array
    {
        $utm = $l->utm ?? [];

        return [
            'id' => $l->id, 'nombre' => $l->nombre, 'email' => $l->email, 'telefono' => $l->telefono,
            'mensaje' => $l->mensaje, 'notas' => $l->notas, 'fuente' => $l->fuente, 'oferta' => $l->oferta,
            'etapa' => $l->etapa, 'tenant_id' => $l->tenant_id,
            'utm_source' => $l->utm_source ?? ($utm['utm_source'] ?? null),
            'utm_medium' => $l->utm_medium ?? ($utm['utm_medium'] ?? null),
            'utm_campaign' => $l->utm_campaign ?? ($utm['utm_campaign'] ?? null),
            'fecha' => $l->created_at?->format('d/m/Y H:i'),
            'hace' => $l->created_at?->locale('es')->diffForHumans(),
            'wa' => $l->telefono ? 'https://wa.me/' . (strlen($d = preg_replace('/\D/', '', $l->telefono)) === 9 ? '51' . $d : $d) : null,
        ];
    }

    public function pagina(Request $request): View
    {
        $leads = $this->consulta($request)->limit(500)->get();
        $filas = $leads->map(fn ($l) => $this->fila($l))->values();

        $porEtapa = $filas->countBy('etapa');
        $total = max(1, $filas->count());

        return view('superadmin.leads', [
            'leads' => $filas,
            'estados' => LandingLead::NOMBRES_ESTADO,
            'fuentes' => LandingLead::query()->distinct()->pluck('fuente')->filter()->values(),
            'campanas' => LandingLead::query()->whereNotNull('utm_campaign')->distinct()->pluck('utm_campaign')->values(),
            'resumen' => [
                'total' => $filas->count(),
                'nuevos' => $porEtapa['nuevo'] ?? 0,
                'ganados' => $porEtapa['ganado'] ?? 0,
                'conversion' => round(($porEtapa['ganado'] ?? 0) * 100 / $total, 1),
                'semana' => $leads->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'porFuente' => $filas->groupBy(fn ($l) => $l['utm_source'] ?: $l['fuente'])->map->count()->sortDesc()->take(6),
            'filtros' => $request->only(['q', 'fuente', 'campana', 'desde']),
        ]);
    }

    public function actualizar(Request $request, LandingLead $lead): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['sometimes', 'in:' . implode(',', LandingLead::ESTADOS)],
            'notas' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);
        if (isset($datos['estado']) && $datos['estado'] !== 'nuevo' && ! $lead->contactado_at) {
            $datos['contactado_at'] = now();
        }
        $lead->update($datos);

        return response()->json(['ok' => true, 'lead' => $this->fila($lead->fresh())]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $leads = $this->consulta($request)->get();

        return response()->streamDownload(function () use ($leads) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Fecha', 'Nombre', 'Correo', 'Teléfono', 'Etapa', 'Origen', 'utm_source', 'utm_medium', 'utm_campaign', 'Oferta', 'Mensaje', 'Notas']);
            foreach ($leads as $l) {
                $r = $this->fila($l);
                fputcsv($f, [$r['fecha'], $r['nombre'], $r['email'], $r['telefono'], LandingLead::NOMBRES_ESTADO[$r['etapa']],
                    $r['fuente'], $r['utm_source'], $r['utm_medium'], $r['utm_campaign'], $r['oferta'], $r['mensaje'], $r['notas']]);
            }
            fclose($f);
        }, 'leads-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
