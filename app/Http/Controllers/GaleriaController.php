<?php

namespace App\Http\Controllers;

use App\Models\GaleriaItem;
use App\Models\Tenant;
use App\Support\Imagenes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Galeria de trabajos (GEST-80): subida múltiple arrastrando, categoría, orden arrastrando,
 * publicar u ocultar, miniaturas webp y límite de fotos por plan. Página pública (GEST-81).
 */
class GaleriaController extends DashboardController
{
    public const LIMITE_DEFECTO = 20;

    public function pagina()
    {
        $this->compartirLayout();

        $items = GaleriaItem::query()->orderBy('orden')->orderByDesc('id')->get();

        return view('admin.modulos.galeria', [
            'titulo' => 'Galería de trabajos',
            'modulo' => 'galeria',
            'items' => $items->map(fn (GaleriaItem $i) => $this->formatear($i))->values(),
            'limite' => $this->limite(),
            'slug' => Tenant::find(auth()->user()->tenant_id)?->slug,
        ]);
    }

    /** Fotos permitidas por el plan del negocio. */
    private function limite(): int
    {
        $tenant = Tenant::find(auth()->user()->tenant_id);
        if ($tenant?->plan_id && Schema::hasColumn('planes_saas', 'max_fotos_galeria')) {
            return (int) (DB::table('planes_saas')->where('id', $tenant->plan_id)->value('max_fotos_galeria') ?: self::LIMITE_DEFECTO);
        }

        return self::LIMITE_DEFECTO;
    }

    private function formatear(GaleriaItem $i): array
    {
        return [
            'id' => $i->id, 'titulo' => $i->titulo, 'descripcion' => $i->descripcion, 'categoria' => $i->categoria,
            'imagen_url' => $i->imagen_url, 'miniatura_url' => $i->miniatura_url ?: $i->imagen_url,
            'activo' => (bool) $i->activo, 'orden' => (int) $i->orden,
        ];
    }

    /** Sube varias fotos a la vez o agrega una por enlace https. */
    public function guardar(Request $request): JsonResponse|RedirectResponse
    {
        $datos = $request->validate([
            'fotos' => ['nullable', 'array', 'max:20'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'titulo' => ['nullable', 'string', 'max:120', 'required_without:fotos'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'imagen_url' => ['nullable', 'url', 'max:500', 'starts_with:https://', 'required_without:fotos'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'fotos.*.image' => 'Solo se aceptan fotos (JPG, PNG o WEBP).',
            'fotos.*.mimes' => 'Solo se aceptan fotos JPG, PNG o WEBP.',
            'fotos.*.max' => 'Cada foto puede pesar hasta 8 MB.',
            'fotos.max' => 'Sube hasta 20 fotos a la vez.',
            'imagen_url.required_without' => 'Sube una foto o pega un enlace https.',
            'titulo.required_without' => 'Escribe un título.',
        ]);

        $tenantId = (int) auth()->user()->tenant_id;
        $archivos = $request->file('fotos', []);
        $nuevas = $archivos ? count($archivos) : 1;
        $usadas = GaleriaItem::query()->count();
        $limite = $this->limite();
        if ($usadas + $nuevas > $limite) {
            throw ValidationException::withMessages(['fotos' => "Tu plan permite {$limite} fotos en la galería y ya tienes {$usadas}. Elimina algunas o cambia de plan."]);
        }

        $orden = (int) GaleriaItem::query()->max('orden') + 1;
        $creados = [];
        $categoria = trim((string) ($datos['categoria'] ?? '')) ?: null;

        if ($archivos) {
            foreach ($archivos as $archivo) {
                $img = Imagenes::guardarWebp($archivo, "galeria/{$tenantId}");
                $nombre = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
                $creados[] = GaleriaItem::create([
                    'tenant_id' => $tenantId,
                    'titulo' => ($datos['titulo'] ?? null) ?: mb_substr(ucfirst(trim(preg_replace('/[_\-]+/', ' ', $nombre))), 0, 120) ?: 'Trabajo',
                    'descripcion' => $datos['descripcion'] ?? null,
                    'categoria' => $categoria,
                    'imagen_url' => $img['url'],
                    'miniatura_url' => $img['miniatura_url'],
                    'archivo' => $img['archivo'],
                    'orden' => $orden++,
                    'activo' => true,
                ]);
            }
        } else {
            $creados[] = GaleriaItem::create([
                'tenant_id' => $tenantId, 'titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'] ?? null,
                'categoria' => $categoria, 'imagen_url' => $datos['imagen_url'], 'orden' => $datos['orden'] ?? $orden, 'activo' => true,
            ]);
        }

        $mensaje = count($creados) === 1 ? 'Trabajo agregado a la galería.' : count($creados) . ' fotos agregadas a la galería.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje, 'items' => collect($creados)->map(fn ($i) => $this->formatear($i))]);
        }

        return back()->with('ok', $mensaje);
    }

    public function actualizar(Request $request, GaleriaItem $item): JsonResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:60'],
        ], ['titulo.required' => 'Escribe un título.']);
        $datos['categoria'] = trim((string) ($datos['categoria'] ?? '')) ?: null;
        $item->update($datos);

        return response()->json(['message' => 'Cambios guardados.', 'item' => $this->formatear($item)]);
    }

    /** Guarda el nuevo orden después de arrastrar. */
    public function ordenar(Request $request): JsonResponse
    {
        $datos = $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer']]);
        $propios = GaleriaItem::query()->whereIn('id', $datos['ids'])->pluck('id')->all();
        foreach (array_values($datos['ids']) as $pos => $id) {
            if (in_array($id, $propios, true)) {
                GaleriaItem::query()->whereKey($id)->update(['orden' => $pos + 1]);
            }
        }

        return response()->json(['message' => 'Orden guardado.']);
    }

    public function alternar(Request $request, GaleriaItem $item): JsonResponse|RedirectResponse
    {
        $item->update(['activo' => ! $item->activo]);
        $mensaje = $item->activo ? 'Foto publicada.' : 'Foto oculta.';

        return $request->expectsJson() ? response()->json(['message' => $mensaje, 'activo' => $item->activo]) : back()->with('ok', $mensaje);
    }

    public function eliminar(Request $request, GaleriaItem $item): JsonResponse|RedirectResponse
    {
        Imagenes::borrar($item->archivo);
        $item->delete();

        return $request->expectsJson() ? response()->json(['message' => 'Foto eliminada.']) : back()->with('ok', 'Trabajo eliminado.');
    }

    /** Pagina publica con los trabajos activos del negocio. */
    public function publica(string $slug): Response
    {
        $negocio = Tenant::query()->where('slug', $slug)->whereIn('estado', ['activo', 'trial'])->firstOrFail();

        $items = GaleriaItem::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('activo', true)->orderBy('orden')->orderByDesc('id')->get();

        return response()->view('publico.galeria', ['negocio' => $negocio, 'items' => $items]);
    }
}
