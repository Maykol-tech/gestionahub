<?php

namespace Database\Seeders;

use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Datos iniciales del panel: planes, negocio demo, usuario administrador,
 * catalogo, clientes y pedidos de los ultimos 30 dias.
 * Se puede ejecutar varias veces: no duplica nada.
 *
 *   php artisan db:seed --force
 */
class GestionaHubSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@gestionahub.pe';
    public const ADMIN_PASSWORD = 'Admin2026!';

    public function run(): void
    {
        // 1) Planes del SaaS
        $planes = [
            ['emprendedor', 'Emprendedor', 49, 490, 2, 1],
            ['negocio', 'Negocio', 89, 890, 5, 2],
            ['pro', 'Pro', 149, 1490, 15, 5],
        ];
        foreach ($planes as [$slug, $nombre, $mes, $anio, $usuarios, $sucursales]) {
            DB::table('planes_saas')->updateOrInsert(['slug' => $slug], [
                'nombre' => $nombre, 'precio_mensual' => $mes, 'precio_anual' => $anio,
                'max_usuarios' => $usuarios, 'max_sucursales' => $sucursales, 'activo' => true,
            ]);
        }

        // 2) Roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['superadmin', 'admin', 'vendedor', 'cajero', 'delivery', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        // 3) Negocio demo (tenant 1)
        $tenant = Tenant::find(1);
        if (! $tenant) {
            $tenant = new Tenant();
            $tenant->id = 1;
        }
        $tenant->fill([
            'nombre_negocio' => $tenant->nombre_negocio ?: 'El Clásico',
            'email' => $tenant->email ?: 'contacto@elclasico.pe',
            'telefono' => $tenant->telefono ?: '987654321',
            'ruc' => $tenant->ruc ?: '20601234567',
            'plan_id' => $tenant->plan_id ?: DB::table('planes_saas')->where('slug', 'negocio')->value('id'),
            'estado' => $tenant->estado ?: 'trial',
        ]);
        if (Schema::hasColumn('tenants', 'trial_ends_at') && ! $tenant->trial_ends_at) {
            $tenant->trial_ends_at = now()->addDays(14);
        }
        $tenant->save();

        // 4) Usuario administrador predeterminado
        $admin = User::where('email', self::ADMIN_EMAIL)->first() ?? new User(['email' => self::ADMIN_EMAIL]);
        $admin->fill(['name' => 'Administrador', 'tenant_id' => $tenant->id]);
        // En local siempre se deja la clave de demostracion; en produccion solo al crearlo
        if (! $admin->exists || ! app()->environment('production')) {
            $admin->password = self::ADMIN_PASSWORD;
        }
        $admin->email_verified_at ??= now();
        $admin->save();
        $admin->syncRoles(['admin']);

        // 5) Catalogo, clientes y pedidos (solo si el negocio aun no tiene datos)
        //    Los datos vienen de CatalogoDemoSeeder (una sola fuente, sin duplicar listas)
        $demo = new CatalogoDemoSeeder();
        if (! Producto::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists()) {
            $demo->catalogo($tenant->id);
        }
        if (! CrmCliente::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists()) {
            $demo->clientes($tenant->id);
        }
        if (Schema::hasTable('pedidos') && ! Pedido::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists()) {
            $this->pedidos($tenant->id);
        }

        $this->command?->info('Usuario admin: ' . self::ADMIN_EMAIL . ' / ' . self::ADMIN_PASSWORD);
    }

    private function pedidos(int $t): void
    {
        mt_srand(2026); // siempre los mismos datos

        $clientes = CrmCliente::withoutGlobalScopes()->where('tenant_id', $t)->get();
        $productos = Producto::withoutGlobalScopes()->with(['variantes' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('tenant_id', $t)->get();
        $metodos = ['efectivo', 'yape', 'yape', 'plin', 'tarjeta'];
        $canales = ['whatsapp', 'whatsapp', 'web', 'manual', 'pos'];
        $n = 0;

        DB::transaction(function () use ($t, $clientes, $productos, $metodos, $canales, &$n) {
            for ($dia = 29; $dia >= 0; $dia--) {
                $cantidad = $dia === 0 ? 7 : mt_rand(1, 4);
                for ($k = 0; $k < $cantidad; $k++) {
                    $fecha = Carbon::now('America/Lima')->subDays($dia)->setTime(mt_rand(12, 21), mt_rand(0, 59))->utc();
                    if ($fecha->isFuture()) {
                        $fecha = now()->subMinutes(mt_rand(5, 90));
                    }

                    // Estado segun antiguedad: los de hoy siguen en curso
                    $estado = 'entregado';
                    if ($dia === 0) {
                        $estado = ['pendiente', 'pendiente', 'confirmado', 'preparando', 'en_camino', 'en_camino', 'entregado'][$k];
                    } elseif (mt_rand(1, 18) === 1) {
                        $estado = 'cancelado';
                    }

                    $cliente = $clientes[mt_rand(0, $clientes->count() - 1)];
                    $tipo = mt_rand(1, 4) === 1 ? 'recojo' : 'delivery';
                    $envio = $tipo === 'delivery' ? 5 : 0;

                    // Detalle del pedido
                    $lineas = [];
                    foreach ($productos->random(mt_rand(1, 3)) as $p) {
                        $variante = $p->variantes->isNotEmpty() ? $p->variantes[mt_rand(0, $p->variantes->count() - 1)] : null;
                        $precio = (float) ($variante?->precio ?? $p->precio_oferta ?? $p->precio);
                        $cant = mt_rand(1, 3);
                        $lineas[] = [
                            'tenant_id' => $t, 'producto_id' => $p->id, 'variante_id' => $variante?->id,
                            'nombre' => $p->nombre . ($variante ? ' · ' . $variante->nombre : ''), 'cantidad' => $cant,
                            'precio_unit' => $precio, 'subtotal' => round($precio * $cant, 2),
                            'created_at' => $fecha, 'updated_at' => $fecha,
                        ];
                    }
                    $subtotal = array_sum(array_column($lineas, 'subtotal'));
                    $total = round($subtotal + $envio, 2);

                    $pedidoId = DB::table('pedidos')->insertGetId([
                        'tenant_id' => $t, 'cliente_id' => $cliente->id, 'numero' => 'P-' . str_pad((string) ++$n, 4, '0', STR_PAD_LEFT),
                        'canal' => $canales[mt_rand(0, 4)], 'estado' => $estado, 'tipo' => $tipo,
                        'direccion_entrega' => $tipo === 'delivery' ? $cliente->direccion . ', ' . $cliente->distrito : null,
                        'metodo_pago' => $metodos[mt_rand(0, 4)], 'pagado' => $estado === 'entregado' || mt_rand(0, 1) === 1,
                        'subtotal' => $subtotal, 'costo_envio' => $envio, 'igv' => round($total - $total / 1.18, 2), 'total' => $total,
                        'confirmado_at' => $estado !== 'pendiente' ? $fecha->copy()->addMinutes(3) : null,
                        'entregado_at' => $estado === 'entregado' ? $fecha->copy()->addMinutes(mt_rand(30, 55)) : null,
                        'cancelado_at' => $estado === 'cancelado' ? $fecha->copy()->addMinutes(10) : null,
                        'created_at' => $fecha, 'updated_at' => $estado === 'entregado' ? $fecha->copy()->addMinutes(45) : $fecha,
                    ]);
                    DB::table('pedido_items')->insert(array_map(fn ($l) => $l + ['pedido_id' => $pedidoId], $lineas));
                }
            }
        });
    }
}
