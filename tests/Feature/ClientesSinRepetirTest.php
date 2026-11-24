<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Un celular = un cliente: los pedidos del panel y la API no crean clientes repetidos. */
class ClientesSinRepetirTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $t = Tenant::create(['nombre_negocio' => 'Pollería Prueba', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    public function test_pedido_con_cliente_nuevo_reutiliza_el_mismo_celular(): void
    {
        $producto = Producto::withoutGlobalScopes()->create([
            'tenant_id' => $this->admin->tenant_id, 'nombre' => 'Agua', 'slug' => 'agua', 'precio' => 5, 'stock' => 20, 'stock_minimo' => 0, 'activo' => true,
        ]);
        $pedido = [
            'cliente_nuevo' => true, 'cliente_nombre' => 'Ana Torres', 'cliente_telefono' => '987654321',
            'tipo' => 'recojo', 'metodo_pago' => 'efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ];

        $this->actingAs($this->admin)->postJson(route('admin.pedidos.guardar'), $pedido)->assertSuccessful();
        $this->actingAs($this->admin)->postJson(route('admin.pedidos.guardar'), $pedido)->assertSuccessful();

        $this->assertSame(1, CrmCliente::withoutGlobalScopes()->where('telefono', '987654321')->count());
    }

    public function test_api_no_repite_cliente_con_el_mismo_celular(): void
    {
        $datos = ['nombre' => 'Luis Paz', 'telefono' => '912345678'];

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/clientes', $datos)->assertCreated();
        $this->actingAs($this->admin, 'sanctum')->postJson('/api/clientes', $datos)->assertOk();

        $this->assertSame(1, CrmCliente::withoutGlobalScopes()->where('telefono', '912345678')->count());
    }
}
