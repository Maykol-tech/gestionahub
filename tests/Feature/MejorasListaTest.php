<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Lista de mejoras: rango de fechas en Inicio, precios masivos, etc. */
class MejorasListaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $t = Tenant::create(['nombre_negocio' => 'Minimarket Sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    public function test_inicio_cambia_de_rango(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Ventas hoy')->assertSee('vs ayer');
        $this->actingAs($this->admin)->get(route('admin.dashboard', ['rango' => 'semana']))->assertOk()->assertSee('Ventas últimos 7 días');
        $this->actingAs($this->admin)->get(route('admin.dashboard', ['rango' => 'mes']))->assertOk()->assertSee('Ventas este mes')->assertSee('vs el mes pasado');
    }

    public function test_cambia_precios_por_categoria_con_vista_previa_y_redondeo(): void
    {
        $tid = $this->admin->tenant_id;
        $bebidas = Categoria::withoutGlobalScopes()->create(['tenant_id' => $tid, 'nombre' => 'Bebidas', 'slug' => 'bebidas', 'activo' => true]);
        $a = Producto::withoutGlobalScopes()->create(['tenant_id' => $tid, 'categoria_id' => $bebidas->id, 'nombre' => 'Gaseosa', 'precio' => 10, 'precio_oferta' => 10.5, 'stock' => 1, 'activo' => true]);
        $b = Producto::withoutGlobalScopes()->create(['tenant_id' => $tid, 'nombre' => 'Arroz', 'precio' => 20, 'stock' => 1, 'activo' => true]);
        $url = route('admin.productos.precios_masivo');

        $prev = $this->actingAs($this->admin)->postJson($url, ['alcance' => 'categoria', 'categoria_id' => $bebidas->id, 'operacion' => 'subir_pct', 'valor' => 12, 'redondear' => '90', 'simular' => true])->assertOk()->json();
        $this->assertSame(1, $prev['total']);
        $this->assertEqualsWithDelta(11.9, $prev['ejemplos'][0]['despues'], 0.001);
        $this->assertEquals(10, $a->fresh()->precio);

        $this->actingAs($this->admin)->postJson($url, ['alcance' => 'categoria', 'categoria_id' => $bebidas->id, 'operacion' => 'subir_pct', 'valor' => 12, 'redondear' => '90'])->assertOk();
        $this->assertEquals(11.9, $a->fresh()->precio);
        $this->assertEquals(10.5, $a->fresh()->precio_oferta);
        $this->assertEquals(20, $b->fresh()->precio);

        $this->actingAs($this->admin)->postJson($url, ['alcance' => 'elegidos', 'ids' => [$a->id, $b->id], 'operacion' => 'restar', 'valor' => 2])->assertOk()->assertJsonPath('total', 2);
        $this->assertEquals(9.9, $a->fresh()->precio);
        $this->assertNull($a->fresh()->precio_oferta);
        $this->actingAs($this->admin)->postJson($url, ['alcance' => 'todos', 'operacion' => 'bajar_pct', 'valor' => 95])->assertStatus(422);
        $this->actingAs($this->admin)->get(route('admin.productos'))->assertSee('Cambiar precios');
    }
}
