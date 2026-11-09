<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel de Productos:
 *  GEST-18 CRUD de categorias y productos con variantes
 *  GEST-19 Galeria de imagenes por producto
 *  GEST-20 Control de stock con alertas de minimo
 */
class ProductosPanelController extends DashboardController
{
    private const MAX_IMAGENES = 8;

    // =================================================================
    // PRODUCTOS
    // =================================================================

    public function productos()
    {
        $this->compartirLayout();

        return view('admin.productos', [
            'productos' => $this->listaProductos(),
            'categorias' => $this->listaCategorias(),
        ]);
    }

    public function productosLista()
    {
        return response()->json(['data' => $this->listaProductos(), 'categorias' => $this->listaCategorias()]);
    }

    public function guardarProducto(Request $request)
    {
        return $this->persistir($request, null);
    }

    public function actualizarProducto(Request $request, $id)
    {
        return $this->persistir($request, $this->producto($id));
    }

    public function eliminarProducto($id)
    {
        $producto = $this->producto($id);
        $nombre = $producto->nombre;

        DB::transaction(function () use ($producto) {
            // Borra las imagenes una por una para eliminar tambien los archivos
            ProductoImagen::where('producto_id', $producto->id)->get()->each->delete();
            $producto->variantes()->delete();
            $producto->delete();
        });

        return response()->json(['message' => "Se eliminó «{$nombre}»"]);
    }

    /** GEST-20: reponer stock rapido (producto simple o una variante). */
    public function reponerStock(Request $request, $id)
    {
        $producto = $this->producto($id);
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'variante_id' => [$producto->tiene_variantes ? 'required' : 'nullable', Rule::exists('producto_variantes', 'id')->where('producto_id', $producto->id)],
        ], self::MENSAJES + ['variante_id.required' => 'Elige la variante que vas a reponer.']);

        if ($producto->tiene_variantes) {
            ProductoVariante::where('producto_id', $producto->id)->whereKey($datos['variante_id'])->increment('stock', $datos['cantidad']);
            $producto->recalcularStock();
        } else {
            $producto->increment('stock', $datos['cantidad']);
        }

        $producto->refresh();

        return response()->json([
            'message' => "Stock actualizado: {$producto->nombre} ahora tiene {$producto->stock} und.",
            'data' => $this->formatear($producto->load(['categoria', 'variantes', 'imagenes'])),
        ]);
    }

    // =================================================================
    // CATEGORIAS (GEST-18)
    // =================================================================

    public function guardarCategoria(Request $request)
    {
        return $this->persistirCategoria($request, null);
    }

    public function actualizarCategoria(Request $request, $id)
    {
        return $this->persistirCategoria($request, Categoria::where('tenant_id', $this->tenantId())->findOrFail($id));
    }

    public function eliminarCategoria($id)
    {
        $categoria = Categoria::where('tenant_id', $this->tenantId())->findOrFail($id);
        $enUso = Producto::where('tenant_id', $this->tenantId())->where('categoria_id', $categoria->id)->count();

        // Los productos no se borran: quedan "Sin categoria"
        Producto::where('tenant_id', $this->tenantId())->where('categoria_id', $categoria->id)->update(['categoria_id' => null]);
        $categoria->delete();

        return response()->json([
            'message' => $enUso ? "Categoría eliminada. {$enUso} producto(s) quedaron sin categoría." : 'Categoría eliminada',
            'categorias' => $this->listaCategorias(),
        ]);
    }

    // =================================================================
    // GALERIA (GEST-19)
    // =================================================================

    public function subirImagenes(Request $request, $id)
    {
        $producto = $this->producto($id);
        $request->validate([
            'imagenes' => ['required', 'array', 'min:1'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], self::MENSAJES + [
            'imagenes.required' => 'Elige al menos una imagen.',
            'imagenes.*.max' => 'Cada imagen debe pesar menos de 4 MB.',
            'imagenes.*.image' => 'Solo se permiten imágenes.',
            'imagenes.*.mimes' => 'Formatos permitidos: JPG, PNG o WEBP.',
        ]);

        $actuales = $producto->imagenes()->count();
        $archivos = $request->file('imagenes');
        if ($actuales + count($archivos) > self::MAX_IMAGENES) {
            return response()->json(['message' => 'Cada producto admite hasta ' . self::MAX_IMAGENES . ' imágenes. Ya tienes ' . $actuales . '.'], 422);
        }

        $this->guardarArchivos($producto, $archivos);

        return $this->respuestaGaleria($producto, count($archivos) === 1 ? 'Imagen agregada' : count($archivos) . ' imágenes agregadas');
    }

    public function principalImagen($id, $imagenId)
    {
        $producto = $this->producto($id);
        $imagen = $producto->imagenes()->findOrFail($imagenId);

        $producto->imagenes()->update(['es_principal' => false]);
        $imagen->update(['es_principal' => true]);
        $producto->sincronizarImagenPrincipal();

        return $this->respuestaGaleria($producto, 'Imagen principal actualizada');
    }

    public function ordenarImagenes(Request $request, $id)
    {
        $producto = $this->producto($id);
        $datos = $request->validate(['orden' => ['required', 'array'], 'orden.*' => ['integer']]);

        foreach (array_values($datos['orden']) as $posicion => $imagenId) {
            $producto->imagenes()->whereKey($imagenId)->update(['orden' => $posicion]);
        }

        return $this->respuestaGaleria($producto, 'Orden guardado');
    }

    public function eliminarImagen($id, $imagenId)
    {
        $producto = $this->producto($id);
        $producto->imagenes()->findOrFail($imagenId)->delete();
        $producto->sincronizarImagenPrincipal();

        return $this->respuestaGaleria($producto, 'Imagen eliminada');
    }

    // =================================================================
    // AYUDANTES
    // =================================================================

    protected function producto($id): Producto
    {
        return Producto::where('tenant_id', $this->tenantId())->findOrFail($id);
    }

    protected function listaCategorias()
    {
        $conteo = Producto::where('tenant_id', $this->tenantId())->whereNotNull('categoria_id')
            ->selectRaw('categoria_id, COUNT(*) as n')->groupBy('categoria_id')->pluck('n', 'categoria_id');

        return Categoria::where('tenant_id', $this->tenantId())->orderBy('orden')->orderBy('nombre')->get()
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'activo' => (bool) $c->activo, 'productos' => (int) ($conteo[$c->id] ?? 0)])
            ->values();
    }

    protected function listaProductos()
    {
        return Producto::with(['categoria:id,nombre', 'variantes', 'imagenes'])
            ->where('tenant_id', $this->tenantId())
            ->latest()->limit(500)->get()
            ->map(fn ($p) => $this->formatear($p))
            ->values();
    }

    protected function formatear(Producto $p): array
    {
        $base = $this->formatearProducto($p);

        $variantes = $p->tiene_variantes ? $p->variantes->map(fn ($v) => [
            'id' => $v->id, 'nombre' => $v->nombre, 'sku' => $v->sku,
            'precio' => $v->precio !== null ? (float) $v->precio : null,
            'stock' => (int) $v->stock, 'stock_minimo' => (int) $v->stock_minimo, 'activo' => (bool) $v->activo,
        ])->values() : collect();

        // Con variantes, el producto esta "bajo" si alguna variante activa llego a su minimo
        $variantesBajas = $variantes->filter(fn ($v) => $v['activo'] && $v['stock'] <= $v['stock_minimo'])->count();
        if ($p->activo && $p->tiene_variantes && $base['estado'] === 'activo' && $variantesBajas) {
            $base['estado'] = 'bajo';
        }

        $precios = $variantes->pluck('precio')->filter(fn ($x) => $x !== null);

        return $base + [
            'tiene_variantes' => (bool) $p->tiene_variantes,
            'variantes' => $variantes,
            'variantes_bajas' => $variantesBajas,
            'precio_desde' => $precios->isNotEmpty() ? (float) $precios->min() : null,
            'imagenes' => $p->imagenes->map(fn ($i) => ['id' => $i->id, 'url' => $i->url, 'principal' => (bool) $i->es_principal])->values(),
        ];
    }

    protected function persistir(Request $request, ?Producto $producto)
    {
        $tenantId = $this->tenantId();
        $conVariantes = $request->boolean('tiene_variantes');

        $datos = $request->validate([
            'nombre'          => ['required', 'string', 'max:150'],
            'categoria_id'    => ['nullable', Rule::exists('categorias', 'id')->where('tenant_id', $tenantId)],
            'nueva_categoria' => ['nullable', 'string', 'max:80'],
            'precio'          => [$conVariantes ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999'],
            'precio_oferta'   => ['nullable', 'numeric', 'min:0', 'lt:precio'],
            'stock'           => [$conVariantes ? 'nullable' : 'required', 'integer', 'min:0', 'max:9999999'],
            'stock_minimo'    => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'sku'             => ['nullable', 'string', 'max:60'],
            'descripcion'     => ['nullable', 'string', 'max:2000'],
            'activo'          => ['nullable', 'boolean'],
            'tiene_variantes' => ['nullable', 'boolean'],
            'variantes'       => [$conVariantes ? 'required' : 'nullable', 'array', 'max:50'],
            'variantes.*.id'           => ['nullable', 'integer'],
            'variantes.*.nombre'       => ['required', 'string', 'max:120', 'distinct:ignore_case'],
            'variantes.*.precio'       => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'variantes.*.stock'        => ['required', 'integer', 'min:0', 'max:9999999'],
            'variantes.*.stock_minimo' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'variantes.*.sku'          => ['nullable', 'string', 'max:60'],
            'variantes.*.activo'       => ['nullable', 'boolean'],
            'imagenes'        => ['nullable', 'array', 'max:' . self::MAX_IMAGENES],
            'imagenes.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], self::MENSAJES + [
            'variantes.required' => 'Agrega al menos una variante o desactiva la opción.',
            'variantes.*.nombre.required' => 'Ponle nombre a la variante.',
            'variantes.*.nombre.distinct' => 'Hay dos variantes con el mismo nombre.',
            'variantes.*.stock.required' => 'Indica el stock.',
            'imagenes.*.max' => 'Cada imagen debe pesar menos de 4 MB.',
            'imagenes.*.image' => 'Solo se permiten imágenes.',
            'imagenes.*.mimes' => 'Formatos permitidos: JPG, PNG o WEBP.',
        ]);

        if (! empty($datos['nueva_categoria'])) {
            $nombreCat = trim($datos['nueva_categoria']);
            $categoria = Categoria::where('tenant_id', $tenantId)->where('nombre', $nombreCat)->first()
                ?? Categoria::create(['tenant_id' => $tenantId, 'nombre' => $nombreCat, 'slug' => Str::slug($nombreCat) ?: 'categoria']);
            $datos['categoria_id'] = $categoria->id;
        }

        $variantes = $conVariantes ? collect($datos['variantes'] ?? []) : collect();

        $campos = collect($datos)->only(['nombre', 'categoria_id', 'precio', 'precio_oferta', 'stock', 'stock_minimo', 'sku', 'descripcion'])->all();
        $campos['stock_minimo'] = $campos['stock_minimo'] ?? 0;
        $campos['activo'] = $request->has('activo') ? $request->boolean('activo') : ($producto->activo ?? true);
        $campos['tiene_variantes'] = $conVariantes;
        if ($conVariantes) {
            // Precio base = la variante mas barata; stock = suma de variantes activas
            $campos['precio'] = $variantes->pluck('precio')->filter(fn ($x) => $x !== null && $x !== '')->min() ?? ($datos['precio'] ?? 0);
            $campos['precio_oferta'] = null;
            $campos['stock'] = (int) $variantes->filter(fn ($v) => ! isset($v['activo']) || filter_var($v['activo'], FILTER_VALIDATE_BOOLEAN))->sum('stock');
        }

        if (! $producto || $producto->nombre !== $campos['nombre']) {
            $campos['slug'] = $this->slugProducto($campos['nombre'], $producto?->id);
        }

        $producto = DB::transaction(function () use ($producto, $campos, $tenantId, $variantes, $conVariantes) {
            if ($producto) {
                $producto->update($campos);
            } else {
                $producto = Producto::create($campos + ['tenant_id' => $tenantId]);
            }

            // Sincroniza variantes: actualiza las existentes, crea las nuevas y borra las quitadas
            $conservar = [];
            foreach ($variantes->values() as $orden => $v) {
                $valores = [
                    'nombre' => trim($v['nombre']), 'atributos' => ['presentacion' => trim($v['nombre'])],
                    'precio' => ($v['precio'] ?? '') === '' ? null : $v['precio'],
                    'stock' => (int) $v['stock'], 'stock_minimo' => (int) ($v['stock_minimo'] ?? 0),
                    'sku' => ($v['sku'] ?? '') ?: null, 'orden' => $orden,
                    'activo' => ! isset($v['activo']) || filter_var($v['activo'], FILTER_VALIDATE_BOOLEAN),
                ];
                $existente = ! empty($v['id']) ? $producto->variantes()->whereKey($v['id'])->first() : null;
                if ($existente) {
                    $existente->update($valores);
                    $conservar[] = $existente->id;
                } else {
                    $conservar[] = $producto->variantes()->create($valores + ['tenant_id' => $tenantId])->id;
                }
            }
            $producto->variantes()->when($conVariantes, fn ($q) => $q->whereNotIn('id', $conservar))->delete();

            return $producto;
        });

        $archivos = $request->file('imagenes', []);
        if ($archivos) {
            $actuales = $producto->imagenes()->count();
            $this->guardarArchivos($producto, array_slice($archivos, 0, max(0, self::MAX_IMAGENES - $actuales)));
        }

        $nuevo = ! $request->route('id');

        return response()->json([
            'message' => $nuevo ? 'Producto creado correctamente' : 'Producto actualizado',
            'data' => $this->formatear($producto->fresh(['categoria', 'variantes', 'imagenes'])),
            'categorias' => $this->listaCategorias(),
        ], $nuevo ? 201 : 200);
    }

    protected function persistirCategoria(Request $request, ?Categoria $categoria)
    {
        $tenantId = $this->tenantId();
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80',
                Rule::unique('categorias', 'nombre')->where('tenant_id', $tenantId)->ignore($categoria?->id)],
            'activo' => ['nullable', 'boolean'],
        ], self::MENSAJES + ['nombre.unique' => 'Ya tienes una categoría con ese nombre.']);

        $valores = ['nombre' => trim($datos['nombre']), 'slug' => Str::slug($datos['nombre']) ?: 'categoria'];
        if ($request->has('activo')) {
            $valores['activo'] = $request->boolean('activo');
        }

        if ($categoria) {
            $categoria->update($valores);
        } else {
            $orden = (int) Categoria::where('tenant_id', $tenantId)->max('orden') + 1;
            $categoria = Categoria::create($valores + ['tenant_id' => $tenantId, 'orden' => $orden, 'activo' => true]);
        }

        return response()->json([
            'message' => $request->route('id') ? 'Categoría actualizada' : 'Categoría creada',
            'data' => ['id' => $categoria->id],
            'categorias' => $this->listaCategorias(),
        ], $request->route('id') ? 200 : 201);
    }

    protected function guardarArchivos(Producto $producto, array $archivos): void
    {
        $tenantId = $this->tenantId();
        $orden = (int) $producto->imagenes()->max('orden');
        $hayPrincipal = $producto->imagenes()->where('es_principal', true)->exists();

        foreach ($archivos as $archivo) {
            $ruta = $archivo->store("productos/{$tenantId}/{$producto->id}", 'public');
            ProductoImagen::create([
                'tenant_id' => $tenantId, 'producto_id' => $producto->id, 'ruta' => $ruta,
                'alt' => $producto->nombre, 'orden' => ++$orden, 'es_principal' => ! $hayPrincipal,
            ]);
            $hayPrincipal = true;
        }

        $producto->sincronizarImagenPrincipal();
    }

    protected function respuestaGaleria(Producto $producto, string $mensaje)
    {
        return response()->json([
            'message' => $mensaje,
            'data' => $this->formatear($producto->fresh(['categoria', 'variantes', 'imagenes'])),
        ]);
    }
}
