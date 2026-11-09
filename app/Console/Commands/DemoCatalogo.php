<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Catalogo de ejemplo: agrega categorias y productos y les pone una foto real
 * de cada plato o prenda (tomada de Wikipedia, imagenes de licencia libre).
 * Si un producto ya existe solo le cambia la foto; no toca precio ni stock.
 * Uso: php artisan demo:catalogo
 */
class DemoCatalogo extends Command
{
    protected $signature = 'demo:catalogo {--tenant= : Id del negocio (por defecto el que tiene mas productos)} {--sin-fotos : Solo crea productos, sin descargar fotos}';

    protected $description = 'Agrega productos y categorias de ejemplo con fotos que corresponden a cada producto';

    /** [categoria, nombre, precio, oferta, stock, minimo, descripcion, [titulos en Wikipedia es], [titulos en Wikipedia en], otros nombres] */
    private const CATALOGO = [
        ['Bebidas', 'Chicha Morada 1L', 12.00, null, 20, 5, 'Jarra de 1 litro preparada en casa con maíz morado, piña y canela.', ['Chicha morada'], ['Chicha morada'], ['chicha morada']],
        ['Bebidas', 'Inca Kola 1.5L', 9.00, null, 30, 8, 'Gaseosa helada de 1.5 litros.', ['Inca Kola'], ['Inca Kola'], ['inca kola']],
        ['Bebidas', 'Limonada Frozen 1L', 14.00, 12.00, 15, 5, 'Limonada frozen con hierbabuena, jarra de 1 litro.', ['Limonada'], ['Lemonade'], ['limonada frozen']],
        ['Bebidas', 'Jugo de Naranja', 8.00, null, 18, 5, 'Jugo de naranja natural recién exprimido.', ['Jugo de naranja'], ['Orange juice'], []],
        ['Bebidas', 'Café Pasado', 6.00, null, 25, 5, 'Taza de café peruano pasado.', [], ['Coffee'], []],
        ['Entradas', 'Ceviche Clásico', 32.00, null, 12, 4, 'Pescado fresco del día en leche de tigre con camote y choclo.', ['Ceviche'], ['Ceviche'], ['ceviche']],
        ['Entradas', 'Causa Limeña', 18.00, null, 14, 4, 'Causa rellena de pollo con palta y mayonesa.', ['Causa limeña'], ['Causa (food)'], []],
        ['Entradas', 'Papa a la Huancaína', 15.00, null, 16, 4, 'Papas sancochadas bañadas en crema de ají amarillo y queso.', ['Papa a la huancaína'], ['Papa a la huancaína'], []],
        ['Entradas', 'Anticuchos (2 palitos)', 22.00, 19.00, 20, 5, 'Anticuchos de corazón a la parrilla con papa dorada.', ['Anticucho'], ['Anticucho'], ['anticuchos de corazon']],
        ['Entradas', 'Tequeños (6 und)', 16.00, null, 24, 6, 'Tequeños de queso con salsa de guacamole.', ['Tequeño'], ['Tequeño'], []],
        ['Comidas', 'Pollo a la Brasa 1/4', 25.00, null, 10, 3, 'Cuarto de pollo a la brasa con papas fritas y ensalada.', ['Pollo a la brasa'], ['Pollo a la brasa'], ['pollo a la brasa']],
        ['Comidas', 'Lomo Saltado', 34.00, null, 15, 4, 'Lomo fino salteado al wok con cebolla, tomate, papas fritas y arroz.', ['Lomo saltado'], ['Lomo saltado'], ['lomo saltado']],
        ['Comidas', 'Ají de Gallina', 26.00, null, 12, 4, 'Pechuga deshilachada en crema de ají amarillo, con arroz y papa.', ['Ají de gallina'], ['Ají de gallina'], ['aji de gallina']],
        ['Comidas', 'Arroz Chaufa', 24.00, 21.00, 18, 5, 'Arroz chaufa de pollo al estilo chifa.', ['Arroz chaufa'], ['Arroz chaufa'], []],
        ['Comidas', 'Arroz con Pollo', 23.00, null, 14, 4, 'Arroz verde con culantro, pierna de pollo y salsa criolla.', ['Arroz con pollo'], ['Arroz con pollo'], []],
        ['Postres', 'Arroz con Leche', 8.00, null, 12, 4, 'Arroz con leche con canela y clavo de olor.', ['Arroz con leche'], ['Rice pudding'], []],
        ['Postres', 'Torta de Chocolate', 15.00, null, 20, 4, 'Tajada de torta húmeda de chocolate.', ['Tarta de chocolate'], ['Chocolate cake'], []],
        ['Postres', 'Suspiro a la Limeña', 12.00, null, 10, 3, 'Manjar blanco con merengue al oporto.', ['Suspiro a la limeña'], ['Suspiro de limeña'], ['suspiro limeno']],
        ['Postres', 'Mazamorra Morada', 9.00, null, 12, 4, 'Mazamorra de maíz morado con frutas.', ['Mazamorra morada'], ['Mazamorra morada'], []],
        ['Postres', 'Picarones (4 und)', 12.00, null, 16, 4, 'Picarones de zapallo y camote con miel de chancaca.', ['Picarón'], ['Picarones'], []],
        ['Polos', 'Polo Oversize Blanco', 45.00, null, 11, 3, 'Polo oversize de algodón, color blanco.', [], ['T-shirt'], []],
        ['Polos', 'Polo Estampado Negro', 55.00, null, 5, 2, 'Polo de algodón negro con estampado.', [], ['T-shirt'], []],
        ['Polos', 'Polera con Capucha', 89.00, 79.00, 8, 2, 'Polera de algodón con capucha y bolsillo canguro.', ['Sudadera'], ['Hoodie'], []],
        ['Accesorios', 'Gorra Bordada', 35.00, null, 14, 3, 'Gorra de drill con logo bordado.', ['Gorra'], ['Baseball cap'], []],
        ['Accesorios', 'Tote Bag de Tela', 25.00, null, 20, 5, 'Bolsa de tela reutilizable.', [], ['Tote bag'], []],
    ];

    public function handle(): int
    {
        $tenantId = (int) ($this->option('tenant')
            ?: DB::table('productos')->select('tenant_id')->groupBy('tenant_id')->orderByRaw('count(*) desc')->value('tenant_id')
            ?: DB::table('tenants')->orderBy('id')->value('id'));
        if (! $tenantId) {
            $this->error('No hay ningún negocio registrado.');

            return self::FAILURE;
        }
        $this->info("Negocio #{$tenantId}");

        $norm = fn ($t) => Str::of((string) $t)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
        $existentes = Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
        $nuevos = 0;
        $fotos = 0;

        foreach (self::CATALOGO as $i => [$cat, $nombre, $precio, $oferta, $stock, $minimo, $desc, $titulosEs, $titulosEn, $otros]) {
            $categoria = Categoria::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->get()->first(fn ($c) => $norm($c->nombre) === $norm($cat))
                ?? Categoria::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId, 'nombre' => $cat, 'slug' => Str::slug($cat), 'orden' => $i, 'activo' => true,
                ]);

            $nombres = array_map($norm, array_merge([$nombre], $otros));
            $producto = $existentes->first(fn ($p) => in_array($norm($p->nombre), $nombres, true));
            if (! $producto) {
                $producto = Producto::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId, 'categoria_id' => $categoria->id, 'nombre' => $nombre,
                    'slug' => $this->slugLibre($tenantId, $nombre), 'descripcion' => $desc,
                    'precio' => $precio, 'precio_oferta' => $oferta, 'stock' => $stock, 'stock_minimo' => $minimo,
                    'tiene_variantes' => false, 'activo' => true,
                ]);
                $nuevos++;
            } elseif (! $producto->categoria_id) {
                $producto->update(['categoria_id' => $categoria->id]);
            }

            if ($this->option('sin-fotos')) {
                $this->line("  {$nombre}");
                continue;
            }

            $foto = $this->buscarFoto($titulosEs, $titulosEn);
            if (! $foto) {
                $this->warn("  {$nombre}: no se pudo descargar la foto (se deja la actual)");
                continue;
            }
            $this->ponerFoto($producto, $tenantId, $foto);
            if (! $categoria->imagen_url) {
                $categoria->update(['imagen_url' => $producto->fresh()->imagen_url]);
            }
            $fotos++;
            $this->line("  {$nombre}: foto de «{$foto['titulo']}»");
        }

        $this->newLine();
        $this->info("Listo: {$nuevos} productos nuevos y {$fotos} fotos actualizadas.");

        return self::SUCCESS;
    }

    /** Busca la foto principal del articulo en Wikipedia (primero en español). */
    private function buscarFoto(array $titulosEs, array $titulosEn): ?array
    {
        $intentos = array_merge(array_map(fn ($t) => ['es', $t], $titulosEs), array_map(fn ($t) => ['en', $t], $titulosEn));
        foreach ($intentos as [$idioma, $titulo]) {
            try {
                $r = Http::withHeaders(['User-Agent' => 'GestionaHub/1.0 (proyecto academico)'])->timeout(15)
                    ->get("https://{$idioma}.wikipedia.org/w/api.php", [
                        'action' => 'query', 'format' => 'json', 'prop' => 'pageimages', 'piprop' => 'thumbnail',
                        'pithumbsize' => 800, 'redirects' => 1, 'titles' => $titulo,
                    ]);
                $pagina = collect($r->json('query.pages', []))->first();
                $url = $pagina['thumbnail']['source'] ?? null;
                if (! $url) {
                    continue;
                }
                $img = Http::withHeaders(['User-Agent' => 'GestionaHub/1.0 (proyecto academico)'])->timeout(25)->get($url);
                if ($img->successful() && str_starts_with((string) $img->header('Content-Type'), 'image/')) {
                    $ext = match (true) {
                        str_contains($img->header('Content-Type'), 'png') => 'png',
                        str_contains($img->header('Content-Type'), 'webp') => 'webp',
                        default => 'jpg',
                    };

                    return ['titulo' => $pagina['title'] ?? $titulo, 'binario' => $img->body(), 'ext' => $ext];
                }
            } catch (\Throwable $e) {
                // sin internet o articulo sin foto: se prueba el siguiente
            }
        }

        return null;
    }

    /** Reemplaza las fotos del producto por la nueva y la deja como principal. */
    private function ponerFoto(Producto $producto, int $tenantId, array $foto): void
    {
        ProductoImagen::withoutGlobalScopes()->where('producto_id', $producto->id)->get()->each->delete();

        $ruta = "productos/{$tenantId}/{$producto->id}/" . Str::slug($producto->nombre) . '-' . Str::random(6) . '.' . $foto['ext'];
        Storage::disk('public')->put($ruta, $foto['binario']);
        ProductoImagen::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'producto_id' => $producto->id, 'ruta' => $ruta,
            'alt' => $producto->nombre, 'orden' => 1, 'es_principal' => true,
        ]);
        $producto->sincronizarImagenPrincipal();
    }

    private function slugLibre(int $tenantId, string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'producto';
        $slug = $base;
        for ($n = 2; Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('slug', $slug)->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}