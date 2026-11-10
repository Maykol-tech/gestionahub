<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\CrmCliente;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Catalogo y clientes de prueba para cada negocio (tenant).
 *
 * - Crea las categorias y asocia cada producto a la suya (categoria_id).
 * - Crea productos simples y productos con variantes (stock por variante).
 * - Crea clientes de prueba vinculados al negocio (tenant_id).
 *
 * Se puede ejecutar varias veces: si el producto, la categoria o el cliente ya
 * existen en ese negocio no se duplican (se buscan por nombre y por telefono).
 * A diferencia de GestionaHubSeeder, completa tambien negocios que ya tienen
 * algunos productos o clientes.
 *
 *   php artisan db:seed --class=CatalogoDemoSeeder --force            (todos los negocios)
 *   SEED_TENANT_ID=3 php artisan db:seed --class=CatalogoDemoSeeder   (solo el negocio 3)
 *   Docker: docker compose exec -e SEED_TENANT_ID=3 app php artisan db:seed --class=CatalogoDemoSeeder --force
 */
class CatalogoDemoSeeder extends Seeder
{
    /**
     * [categoria, nombre, precio, oferta, stock, minimo, descripcion, variantes [[nombre, precio, stock, minimo]]]
     * Los productos con variantes toman su stock de la suma de las variantes.
     */
    public const PRODUCTOS = [
        ['Pollos a la brasa', 'Pollo a la brasa', 68.00, null, 0, 10, 'Pollo a la leña con papas fritas, ensalada y cremas de la casa.', [
            ['1 pollo entero', 68.00, 24, 8], ['1/2 pollo', 36.00, 30, 10], ['1/4 de pollo', 19.90, 6, 10],
        ]],
        ['Pollos a la brasa', 'Mostrito', 24.90, 22.90, 35, 10, '1/4 de pollo con chaufa, papas y ensalada.', []],
        ['Pollos a la brasa', 'Combo familiar', 89.90, 84.90, 15, 4, '1 pollo entero, papas familiares, ensalada y gaseosa de 1.5 L.', []],
        ['Parrillas', 'Parrilla familiar', 89.00, null, 12, 5, 'Bife, chorizo, anticucho, pollo y mollejas para 4 personas.', []],
        ['Parrillas', 'Anticuchos de corazón', 22.00, null, 4, 8, 'Dos palitos con papa dorada y choclo.', []],
        ['Parrillas', 'Chuleta de cerdo', 29.90, null, 18, 6, 'Chuleta a la parrilla con papas y ensalada.', []],
        ['Platos de fondo', 'Lomo saltado', 34.00, null, 15, 4, 'Lomo fino salteado al wok con cebolla, tomate, papas fritas y arroz.', []],
        ['Platos de fondo', 'Ají de gallina', 26.00, null, 12, 4, 'Pechuga deshilachada en crema de ají amarillo, con arroz y papa.', []],
        ['Platos de fondo', 'Arroz chaufa', 24.00, 21.00, 18, 5, 'Arroz chaufa de pollo al estilo chifa.', []],
        ['Entradas', 'Causa limeña', 18.00, null, 14, 4, 'Causa rellena de pollo con palta y mayonesa.', []],
        ['Entradas', 'Papa a la huancaína', 15.00, null, 16, 4, 'Papas sancochadas bañadas en crema de ají amarillo y queso.', []],
        ['Entradas', 'Tequeños (6 und)', 16.00, null, 24, 6, 'Tequeños de queso con salsa de guacamole.', []],
        ['Bebidas', 'Inca Kola', 0, null, 0, 10, 'Gaseosa helada.', [
            ['500 ml', 4.50, 60, 20], ['1 L', 8.00, 28, 12], ['1.5 L', 10.50, 9, 12],
        ]],
        ['Bebidas', 'Chicha morada', 12.00, null, 22, 8, 'Jarra de 1 litro, preparada en casa.', []],
        ['Bebidas', 'Limonada frozen', 14.00, 12.00, 15, 6, 'Jarra de 1 litro.', []],
        ['Bebidas', 'Agua mineral', 3.50, null, 48, 12, 'Botella de 625 ml, con o sin gas.', []],
        ['Postres', 'Torta de chocolate', 9.90, null, 0, 5, 'Tajada de torta húmeda de chocolate.', []],
        ['Postres', 'Suspiro limeño', 8.50, null, 11, 4, 'Postre tradicional en vaso.', []],
        ['Postres', 'Picarones (4 und)', 12.00, null, 16, 4, 'Picarones de zapallo y camote con miel de chancaca.', []],
        ['Acompañamientos', 'Papas fritas', 9.00, null, 40, 15, 'Porción familiar de papas amarillas.', []],
        ['Acompañamientos', 'Ensalada clásica', 7.50, null, 3, 6, 'Lechuga, tomate, pepino y zanahoria.', []],
        ['Acompañamientos', 'Arroz blanco', 5.00, null, 30, 10, 'Porción de arroz graneado.', []],
    ];

    /** [nombre, telefono, distrito, direccion, fuente] */
    public const CLIENTES = [
        ['María Fernanda Quispe', '987123456', 'Miraflores', 'Av. Larco 742', 'whatsapp'],
        ['Jorge Luis Ramírez', '965874123', 'San Isidro', 'Calle Las Begonias 415', 'landing'],
        ['Carmen Rosa Huamán', '912345678', 'Surco', 'Av. Primavera 1250', 'manual'],
        ['Luis Alberto Torres', '934567812', 'San Borja', 'Av. Aviación 2980', 'referido'],
        ['Ana Lucía Mendoza', '956781234', 'Lince', 'Jr. Risso 330', 'whatsapp'],
        ['Pedro Pablo Castillo', '978123654', 'Jesús María', 'Av. Brasil 1540', 'manual'],
        ['Rosa Elena Vargas', '945612378', 'Magdalena', 'Jr. Castilla 812', 'landing'],
        ['Diego Alonso Chávez', '923456781', 'Pueblo Libre', 'Av. Bolívar 1023', 'whatsapp'],
        ['Valeria Sofía Rojas', '967812345', 'Barranco', 'Av. Grau 455', 'referido'],
        ['Miguel Ángel Paredes', '989456123', 'La Molina', 'Av. Raúl Ferrero 1180', 'manual'],
        ['Lucía Gabriela Flores', '936985214', 'Miraflores', 'Calle Schell 220', 'whatsapp'],
        ['Ricardo Andrés Salazar', '958741236', 'Surquillo', 'Av. Angamos Este 1660', 'landing'],
        ['Patricia Noemí Gutiérrez', '941258963', 'San Miguel', 'Av. La Marina 2355', 'whatsapp'],
        ['Fernando José Cárdenas', '952369874', 'Los Olivos', 'Av. Carlos Izaguirre 870', 'manual'],
        ['Gabriela Alejandra Ríos', '963147852', 'Chorrillos', 'Av. Defensores del Morro 1420', 'referido'],
    ];

    public function run(): void
    {
        $tenants = Tenant::query()
            ->when(env('SEED_TENANT_ID'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->command?->warn('No hay negocios registrados. Ejecuta primero: php artisan db:seed');

            return;
        }

        foreach ($tenants as $tenant) {
            [$productos, $clientes] = DB::transaction(fn () => [
                $this->catalogo((int) $tenant->id),
                $this->clientes((int) $tenant->id),
            ]);
            $this->command?->info("Negocio #{$tenant->id} ({$tenant->nombre_negocio}): {$productos} productos y {$clientes} clientes nuevos.");
        }
    }

    /** Crea categorias y productos que falten en el negocio. Devuelve cuantos productos creo. */
    public function catalogo(int $tenantId): int
    {
        $existentes = Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
        $categorias = Categoria::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
        $creados = 0;

        foreach (self::PRODUCTOS as [$cat, $nombre, $precio, $oferta, $stock, $minimo, $desc, $variantes]) {
            $categoria = $categorias->first(fn ($c) => $this->normalizar($c->nombre) === $this->normalizar($cat));
            if (! $categoria) {
                $categoria = Categoria::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId, 'nombre' => $cat, 'slug' => Str::slug($cat),
                    'orden' => $categorias->count(), 'activo' => true,
                ]);
                $categorias->push($categoria);
            }

            $producto = $existentes->first(fn ($p) => $this->normalizar($p->nombre) === $this->normalizar($nombre));
            if ($producto) {
                // Ya existe: solo se asegura que tenga categoria
                if (! $producto->categoria_id) {
                    $producto->update(['categoria_id' => $categoria->id]);
                }
                continue;
            }

            $producto = Producto::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId, 'categoria_id' => $categoria->id, 'nombre' => $nombre,
                'slug' => $this->slugLibre($tenantId, $nombre), 'descripcion' => $desc,
                'precio' => $variantes ? $variantes[0][1] : $precio, 'precio_oferta' => $oferta,
                'stock' => $variantes ? array_sum(array_column($variantes, 2)) : $stock, 'stock_minimo' => $minimo,
                'sku' => $this->skuLibre($tenantId), 'tiene_variantes' => (bool) $variantes, 'activo' => true,
            ]);

            foreach ($variantes as $i => [$vn, $vp, $vs, $vm]) {
                ProductoVariante::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId, 'producto_id' => $producto->id, 'nombre' => $vn,
                    'atributos' => ['presentacion' => $vn], 'sku' => $producto->sku . '-' . ($i + 1),
                    'precio' => $vp, 'stock' => $vs, 'stock_minimo' => $vm, 'orden' => $i, 'activo' => true,
                ]);
            }

            $existentes->push($producto);
            $creados++;
        }

        return $creados;
    }

    /** Crea los clientes de prueba que falten (se reconocen por telefono). Devuelve cuantos creo. */
    public function clientes(int $tenantId): int
    {
        $telefonos = CrmCliente::withoutGlobalScopes()->where('tenant_id', $tenantId)->pluck('telefono')->filter()->all();
        $creados = 0;

        foreach (self::CLIENTES as $i => [$nombre, $tel, $distrito, $direccion, $fuente]) {
            if (in_array($tel, $telefonos, true)) {
                continue;
            }
            $fecha = now()->subDays(40 - $i * 2);
            CrmCliente::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId, 'nombre' => $nombre, 'telefono' => $tel, 'distrito' => $distrito,
                'direccion' => $direccion, 'fuente' => $fuente, 'activo' => true,
                'email' => Str::slug(Str::before($nombre, ' ')) . '.' . Str::slug(Str::afterLast($nombre, ' ')) . '@gmail.com',
            ])->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->saveQuietly();
            $creados++;
        }

        return $creados;
    }

    private function normalizar(?string $texto): string
    {
        return Str::of((string) $texto)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
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

    private function skuLibre(int $tenantId): string
    {
        $n = Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->count() + 1;
        do {
            $sku = 'DEMO-' . str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
        } while (Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('sku', $sku)->exists());

        return $sku;
    }
}
