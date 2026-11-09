<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * GEST-18 (CRUD productos con variantes) + GEST-19 (galeria de imagenes).
 *
 * Para enviar imagenes usa multipart/form-data. En "update" con archivos
 * envia POST /api/productos/{id} con el campo _method=PUT (PHP no lee
 * archivos en PUT directo).
 */
class ProductoController extends Controller
{
    private const MAX_IMAGENES = 10;

    // ------------------------------------------------------------------
    // CRUD
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = Producto::where('productos.tenant_id', $this->tenantId($request))
            ->with(['categoria:id,nombre,slug', 'imagenPrincipal'])
            ->withCount('variantes');

        if ($buscar = $request->query('q')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('sku', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->boolean('stock_bajo')) {
            $query->stockBajo();
        }

        $porPagina = min(max($request->integer('per_page', 20), 1), 100);

        return response()->json($query->latest()->paginate($porPagina), 200);
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $this->normalizarVariantes($request);
        $datos = $request->validate($this->reglas($tenantId));

        $producto = DB::transaction(function () use ($request, $datos, $tenantId) {
            $producto = Producto::create(array_merge(
                collect($datos)->except(['variantes', 'imagenes'])->all(),
                [
                    'tenant_id'       => $tenantId,
                    'slug'            => $this->slugUnico($datos['nombre'], $tenantId),
                    'stock'           => $datos['stock'] ?? 0,
                    'stock_minimo'    => $datos['stock_minimo'] ?? 0,
                    'tiene_variantes' => ! empty($datos['variantes']),
                ]
            ));

            if (! empty($datos['variantes'])) {
                $this->sincronizarVariantes($producto, $datos['variantes']);
            }

            if ($request->hasFile('imagenes')) {
                $this->guardarImagenes($producto, $request->file('imagenes'));
            }

            return $producto;
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto creado',
            'data'    => $this->cargarDetalle($producto->fresh()),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $producto = $this->buscarProducto($request, $id);

        return response()->json($this->cargarDetalle($producto), 200);
    }

    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);
        $producto = $this->buscarProducto($request, $id);
        $this->normalizarVariantes($request);
        $datos = $request->validate($this->reglas($tenantId, true));

        DB::transaction(function () use ($request, $producto, $datos, $tenantId) {
            $campos = collect($datos)->except(['variantes', 'imagenes', 'eliminar_imagenes'])->all();

            foreach (['stock', 'stock_minimo'] as $campo) {
                if (array_key_exists($campo, $campos) && $campos[$campo] === null) {
                    $campos[$campo] = 0;
                }
            }

            if (isset($campos['nombre']) && $campos['nombre'] !== $producto->nombre) {
                $campos['slug'] = $this->slugUnico($campos['nombre'], $tenantId, $producto->id);
            }

            if (array_key_exists('variantes', $datos)) {
                $campos['tiene_variantes'] = ! empty($datos['variantes']);
            }

            $producto->update($campos);

            if (array_key_exists('variantes', $datos)) {
                $this->sincronizarVariantes($producto, $datos['variantes'] ?? []);
            }

            if (! empty($datos['eliminar_imagenes'])) {
                $producto->imagenes()->whereIn('id', $datos['eliminar_imagenes'])->get()->each->delete();
            }

            if ($request->hasFile('imagenes')) {
                $this->guardarImagenes($producto, $request->file('imagenes'));
            } elseif (! empty($datos['eliminar_imagenes'])) {
                $producto->sincronizarImagenPrincipal();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado',
            'data'    => $this->cargarDetalle($producto->fresh()),
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $producto = $this->buscarProducto($request, $id);

        DB::transaction(function () use ($producto) {
            // Borra uno por uno para eliminar tambien los archivos del disco
            $producto->imagenes()->get()->each->delete();
            $producto->delete();
        });

        return response()->json(['success' => true, 'message' => 'Producto eliminado'], 200);
    }

    // ------------------------------------------------------------------
    // Galeria de imagenes (GEST-19)
    // ------------------------------------------------------------------

    public function subirImagenes(Request $request, $id)
    {
        $producto = $this->buscarProducto($request, $id);

        $request->validate([
            'imagenes'    => ['required', 'array', 'min:1', 'max:' . self::MAX_IMAGENES],
            'imagenes.*'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'variante_id' => ['nullable', Rule::exists('producto_variantes', 'id')->where('producto_id', $producto->id)],
        ]);

        $this->guardarImagenes($producto, $request->file('imagenes'), $request->input('variante_id'));

        return response()->json([
            'success' => true,
            'message' => 'Imagenes subidas',
            'data'    => $producto->imagenes()->get(),
        ], 201);
    }

    public function marcarPrincipal(Request $request, $id, $imagenId)
    {
        $producto = $this->buscarProducto($request, $id);
        $imagen = $producto->imagenes()->findOrFail($imagenId);

        DB::transaction(function () use ($producto, $imagen) {
            $producto->imagenes()->update(['es_principal' => false]);
            $imagen->update(['es_principal' => true]);
            $producto->sincronizarImagenPrincipal();
        });

        return response()->json(['success' => true, 'data' => $producto->imagenes()->get()], 200);
    }

    public function ordenarImagenes(Request $request, $id)
    {
        $producto = $this->buscarProducto($request, $id);

        $datos = $request->validate([
            'orden'   => ['required', 'array', 'min:1'],
            'orden.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($producto, $datos) {
            foreach ($datos['orden'] as $posicion => $imagenId) {
                $producto->imagenes()->whereKey($imagenId)->update(['orden' => $posicion]);
            }
        });

        return response()->json(['success' => true, 'data' => $producto->imagenes()->get()], 200);
    }

    public function eliminarImagen(Request $request, $id, $imagenId)
    {
        $producto = $this->buscarProducto($request, $id);
        $producto->imagenes()->findOrFail($imagenId)->delete();
        $producto->sincronizarImagenPrincipal();

        return response()->json(['success' => true, 'message' => 'Imagen eliminada'], 200);
    }

    // ------------------------------------------------------------------
    // Ayudantes
    // ------------------------------------------------------------------

    /**
     * Tenant del usuario autenticado. Mientras las rutas no usen auth:sanctum
     * se usa el tenant 1, igual que en los demas controladores.
     */
    private function tenantId(Request $request): int
    {
        return (int) ($request->user()?->tenant_id ?? 1);
    }

    private function buscarProducto(Request $request, $id): Producto
    {
        return Producto::where('tenant_id', $this->tenantId($request))->findOrFail($id);
    }

    private function cargarDetalle(Producto $producto): Producto
    {
        return $producto->load(['categoria:id,nombre,slug', 'variantes.imagenes', 'imagenes']);
    }

    private function reglas(int $tenantId, bool $actualizando = false): array
    {
        $req = $actualizando ? 'sometimes' : 'required';

        return [
            'nombre'        => [$req, 'string', 'max:150'],
            'descripcion'   => ['nullable', 'string'],
            'categoria_id'  => ['nullable', Rule::exists('categorias', 'id')->where('tenant_id', $tenantId)],
            'precio'        => [$req, 'numeric', 'min:0'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0'],
            'stock'         => [$actualizando ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'stock_minimo'  => ['nullable', 'integer', 'min:0'],
            'sku'           => ['nullable', 'string', 'max:60'],
            'activo'        => ['sometimes', 'boolean'],

            'variantes'                 => ['sometimes', 'nullable', 'array', 'max:100'],
            'variantes.*.id'            => ['nullable', 'integer'],
            'variantes.*.nombre'        => ['required', 'string', 'max:120'],
            'variantes.*.atributos'     => ['nullable', 'array'],
            'variantes.*.sku'           => ['nullable', 'string', 'max:60', 'distinct'],
            'variantes.*.precio'        => ['nullable', 'numeric', 'min:0'],
            'variantes.*.precio_oferta' => ['nullable', 'numeric', 'min:0'],
            'variantes.*.stock'         => ['nullable', 'integer', 'min:0'],
            'variantes.*.stock_minimo'  => ['nullable', 'integer', 'min:0'],
            'variantes.*.activo'        => ['sometimes', 'boolean'],

            'imagenes'            => ['sometimes', 'array', 'max:' . self::MAX_IMAGENES],
            'imagenes.*'          => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'eliminar_imagenes'   => ['sometimes', 'array'],
            'eliminar_imagenes.*' => ['integer'],
        ];
    }

    /** Permite enviar "variantes" como texto JSON dentro de un form-data. */
    private function normalizarVariantes(Request $request): void
    {
        if (is_string($request->input('variantes'))) {
            $decodificado = json_decode($request->input('variantes'), true);
            $request->merge(['variantes' => is_array($decodificado) ? $decodificado : []]);
        }
    }

    /**
     * Crea, actualiza y elimina variantes segun la lista recibida.
     * Las que traen "id" se actualizan; sin "id" se crean; las que no vienen se eliminan.
     */
    private function sincronizarVariantes(Producto $producto, array $variantes): void
    {
        $existentes = $producto->variantes()->get()->keyBy('id');
        $conservar = [];

        try {
            foreach (array_values($variantes) as $i => $v) {
                $datos = [
                    'nombre'        => $v['nombre'],
                    'atributos'     => $v['atributos'] ?? null,
                    'sku'           => $v['sku'] ?? null,
                    'precio'        => $v['precio'] ?? null,
                    'precio_oferta' => $v['precio_oferta'] ?? null,
                    'stock'         => $v['stock'] ?? 0,
                    'stock_minimo'  => $v['stock_minimo'] ?? 0,
                    'activo'        => $v['activo'] ?? true,
                    'orden'         => $i,
                ];

                if (! empty($v['id'])) {
                    $variante = $existentes->get($v['id']);
                    if (! $variante) {
                        throw ValidationException::withMessages([
                            "variantes.$i.id" => 'La variante no pertenece a este producto.',
                        ]);
                    }
                    $variante->update($datos);
                } else {
                    $variante = $producto->variantes()->create($datos + ['tenant_id' => $producto->tenant_id]);
                }

                $conservar[] = $variante->id;
            }
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'variantes' => 'Hay un SKU de variante que ya existe en tu negocio.',
            ]);
        }

        $producto->variantes()->whereNotIn('id', $conservar)->delete();
        $producto->unsetRelation('variantes');
        $producto->recalcularStock();
    }

    private function guardarImagenes(Producto $producto, array $archivos, $varianteId = null): void
    {
        $actuales = $producto->imagenes()->count();

        if ($actuales + count($archivos) > self::MAX_IMAGENES) {
            throw ValidationException::withMessages([
                'imagenes' => 'Maximo ' . self::MAX_IMAGENES . " imagenes por producto (ya tiene $actuales).",
            ]);
        }

        $orden = $actuales === 0 ? -1 : (int) $producto->imagenes()->max('orden');

        foreach ($archivos as $archivo) {
            $ruta = $archivo->store("productos/{$producto->tenant_id}/{$producto->id}", 'public');

            ProductoImagen::create([
                'tenant_id'   => $producto->tenant_id,
                'producto_id' => $producto->id,
                'variante_id' => $varianteId,
                'ruta'        => $ruta,
                'alt'         => $producto->nombre,
                'orden'       => ++$orden,
            ]);
        }

        $producto->sincronizarImagenPrincipal();
    }

    private function slugUnico(string $nombre, int $tenantId, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'producto';
        $slug = $base;
        $n = 2;

        while (
            Producto::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
                ->exists()
        ) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
