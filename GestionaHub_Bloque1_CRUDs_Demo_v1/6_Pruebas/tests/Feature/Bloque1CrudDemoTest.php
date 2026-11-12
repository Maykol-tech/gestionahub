<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Proveedor;
use App\Models\Repartidor;
use App\Models\Tenant;
use App\Models\User;
use App\Support\LimpiezaTextos;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Bloque 1: CRUD de repartidores y proveedores, datos de demo y limpieza de textos. */
class Bloque1CrudDemoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'vendedor', 'cajero', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->tenant = Tenant::create(['nombre_negocio' => 'Bodega Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => 'ana@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    private function repartidor(array $datos = [], ?int $tenantId = null): Repartidor
    {
        return Repartidor::withoutGlobalScopes()->create($datos + [
            'tenant_id' => $tenantId ?? $this->tenant->id,
            'nombre' => 'Repartidor ' . uniqid(),
            'telefono' => '9' . random_int(10000000, 99999999),
            'vehiculo' => 'moto',
            'activo' => true,
            'disponible' => true,
        ]);
    }

    private function pedido(array $datos = []): Pedido
    {
        $p = new Pedido;
        $p->forceFill(array_merge([
            'tenant_id' => $this->tenant->id,
            'numero' => 'D-' . uniqid(),
            'canal' => 'whatsapp',
            'tipo' => 'delivery',
            'estado' => 'pendiente',
            'total' => 25,
        ], $datos))->save();

        return $p;
    }

    public function test_admin_agrega_repartidor_con_placa_y_aparece_en_la_lista(): void
    {
        $r = $this->actingAs($this->admin)->postJson('/admin/repartidores/lista', [
            'nombre' => '  Pedro Gómez ', 'telefono' => '987 654 321', 'dni' => '12345678', 'vehiculo' => 'moto', 'placa' => 'abc-123',
        ]);
        $r->assertStatus(201)->assertJsonPath('success', true)->assertJsonPath('data.placa', 'ABC-123');

        $this->assertDatabaseHas('repartidores', ['tenant_id' => $this->tenant->id, 'nombre' => 'Pedro Gómez', 'placa' => 'ABC-123', 'activo' => true]);
        $this->actingAs($this->admin)->get('/admin/repartidores')->assertOk()->assertSee('Pedro Gómez')->assertSee('ABC-123');
    }

    public function test_validacion_de_repartidor_muestra_errores_por_campo(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/repartidores/lista', ['nombre' => '', 'vehiculo' => 'moto'])
            ->assertStatus(422)->assertJsonValidationErrors('nombre');
        $this->actingAs($this->admin)->postJson('/admin/repartidores/lista', ['nombre' => 'Ana', 'vehiculo' => 'avion'])
            ->assertStatus(422)->assertJsonValidationErrors('vehiculo');
        $this->actingAs($this->admin)->postJson('/admin/repartidores/lista', ['nombre' => 'Ana', 'vehiculo' => 'moto', 'placa' => '<b>x</b>'])
            ->assertStatus(422)->assertJsonValidationErrors('placa');
        $this->actingAs($this->admin)->postJson('/admin/repartidores/lista', ['nombre' => 'Ana', 'vehiculo' => 'moto', 'telefono' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors('telefono');

        $this->repartidor(['telefono' => '911222333']);
        $this->actingAs($this->admin)->postJson('/admin/repartidores/lista', ['nombre' => 'Ana', 'vehiculo' => 'moto', 'telefono' => '911222333'])
            ->assertStatus(422)->assertJsonValidationErrors('telefono');
        $this->assertDatabaseCount('repartidores', 1);
    }

    public function test_editar_repartidor_no_choca_con_su_propio_telefono(): void
    {
        $r = $this->repartidor(['nombre' => 'Luis', 'telefono' => '911000111', 'vehiculo' => 'moto']);

        $this->actingAs($this->admin)->putJson("/admin/repartidores/lista/{$r->id}", [
            'nombre' => 'Luis Quispe', 'telefono' => '911000111', 'vehiculo' => 'auto', 'placa' => 'QWE-789',
        ])->assertOk()->assertJsonPath('data.nombre', 'Luis Quispe');

        $r->refresh();
        $this->assertSame('auto', $r->vehiculo);
        $this->assertSame('QWE-789', $r->placa);
    }

    public function test_activar_y_desactivar_repartidor(): void
    {
        $r = $this->repartidor(['activo' => true, 'disponible' => true]);

        $this->actingAs($this->admin)->patchJson("/admin/repartidores/lista/{$r->id}/estado")
            ->assertOk()->assertJsonPath('data.activo', false);
        $this->assertFalse($r->fresh()->disponible);

        $this->actingAs($this->admin)->patchJson("/admin/repartidores/lista/{$r->id}/estado")
            ->assertOk()->assertJsonPath('data.activo', true);
    }

    public function test_eliminar_es_logico_y_no_se_permite_con_pedidos_en_curso(): void
    {
        $enCurso = $this->repartidor();
        $this->pedido(['repartidor_id' => $enCurso->id, 'estado' => 'en_camino']);
        $this->actingAs($this->admin)->deleteJson("/admin/repartidores/lista/{$enCurso->id}")
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseHas('repartidores', ['id' => $enCurso->id, 'deleted_at' => null]);

        // Con historial ya entregado: se borra logicamente y el historial queda
        $conHistorial = $this->repartidor();
        $entrega = $this->pedido(['repartidor_id' => $conHistorial->id, 'estado' => 'entregado', 'entregado_at' => now()]);
        $this->actingAs($this->admin)->deleteJson("/admin/repartidores/lista/{$conHistorial->id}")->assertOk();
        $this->assertSoftDeleted('repartidores', ['id' => $conHistorial->id]);
        $this->assertSame($conHistorial->id, $entrega->fresh()->repartidor_id);
    }

    public function test_asignar_pedido_abierto_a_un_repartidor_activo(): void
    {
        $r = $this->repartidor();
        $pedido = $this->pedido(['estado' => 'confirmado']);

        $this->actingAs($this->admin)->postJson("/admin/repartidores/lista/{$r->id}/asignar", ['pedido_id' => $pedido->id])
            ->assertOk()->assertJsonPath('success', true);
        $pedido->refresh();
        $this->assertSame($r->id, $pedido->repartidor_id);
        $this->assertNotNull($pedido->asignado_at);

        // Ya tiene repartidor: no se reasigna desde aqui
        $otro = $this->repartidor();
        $this->actingAs($this->admin)->postJson("/admin/repartidores/lista/{$otro->id}/asignar", ['pedido_id' => $pedido->id])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame($r->id, $pedido->fresh()->repartidor_id);
    }

    public function test_no_asigna_pedidos_entregados_ni_a_repartidores_inactivos(): void
    {
        $r = $this->repartidor();
        $entregado = $this->pedido(['estado' => 'entregado', 'entregado_at' => now()]);
        $this->actingAs($this->admin)->postJson("/admin/repartidores/lista/{$r->id}/asignar", ['pedido_id' => $entregado->id])
            ->assertStatus(422);

        $inactivo = $this->repartidor(['activo' => false]);
        $abierto = $this->pedido();
        $this->actingAs($this->admin)->postJson("/admin/repartidores/lista/{$inactivo->id}/asignar", ['pedido_id' => $abierto->id])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertNull($abierto->fresh()->repartidor_id);
    }

    public function test_listado_paginado_de_8_y_busqueda(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->repartidor(['nombre' => 'Repartidor ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }
        $this->repartidor(['nombre' => 'Zoila Especial', 'placa' => 'ZZZ-999']);

        $pagina1 = $this->actingAs($this->admin)->get('/admin/repartidores');
        $pagina1->assertOk();
        $this->assertCount(8, $pagina1->viewData('repartidores'));
        $this->assertSame(11, $pagina1->viewData('repartidores')->total());

        $this->actingAs($this->admin)->get('/admin/repartidores?q=zzz-999')->assertOk()
            ->assertSee('Zoila Especial')->assertDontSee('Repartidor 01');
    }

    public function test_cajero_no_puede_gestionar_repartidores(): void
    {
        $caja = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Caja', 'email' => 'caja@test.pe', 'password' => 'Secreto123!']);
        $caja->assignRole('cajero');

        $this->actingAs($caja)->get('/admin/repartidores')->assertForbidden();
        $this->actingAs($caja)->postJson('/admin/repartidores/lista', ['nombre' => 'Ana', 'vehiculo' => 'moto'])->assertForbidden();
        $this->assertDatabaseCount('repartidores', 0);
    }

    public function test_un_negocio_no_ve_ni_cambia_repartidores_de_otro(): void
    {
        $otro = Tenant::create(['nombre_negocio' => 'Otro', 'email' => uniqid() . '@o.pe', 'estado' => 'activo']);
        $ajeno = $this->repartidor(['nombre' => 'Ajeno'], $otro->id);

        $this->actingAs($this->admin)->putJson("/admin/repartidores/lista/{$ajeno->id}", ['nombre' => 'Hackeado', 'vehiculo' => 'moto'])
            ->assertNotFound();
        $this->actingAs($this->admin)->patchJson("/admin/repartidores/lista/{$ajeno->id}/estado")->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/repartidores')->assertDontSee('Ajeno');
        $this->assertSame('Ajeno', $ajeno->fresh()->nombre);
    }

    public function test_el_menu_muestra_repartidores(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('/admin/repartidores');
    }

    public function test_crud_de_proveedores_funciona_con_las_rutas_del_panel(): void
    {
        $crear = $this->actingAs($this->admin)->postJson('/admin/proveedores/lista', [
            'nombre' => 'Distribuidora Demo', 'ruc' => '20123456789', 'contacto' => 'Ana', 'telefono' => '987654321', 'activo' => true,
        ]);
        $crear->assertOk()->assertJsonPath('success', true);
        $id = $crear->json('proveedor.id');

        $this->actingAs($this->admin)->putJson("/admin/proveedores/lista/{$id}", [
            'nombre' => 'Distribuidora Demo SAC', 'ruc' => '20123456789', 'activo' => false,
        ])->assertOk()->assertJsonPath('proveedor.nombre', 'Distribuidora Demo SAC');

        $this->actingAs($this->admin)->deleteJson("/admin/proveedores/lista/{$id}")->assertOk();
        $this->assertSoftDeleted('proveedores', ['id' => $id]);
    }

    public function test_seeder_de_demo_crea_5_repartidores_5_proveedores_y_15_productos_con_imagen(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->assertSame(5, Repartidor::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(5, Proveedor::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(15, Producto::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(15, ProductoImagen::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(1, Producto::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('stock', 0)->count());

        // Repetir no duplica nada
        $this->seed(DemoDataSeeder::class);
        $this->assertSame(5, Repartidor::withoutGlobalScopes()->count());
        $this->assertSame(15, Producto::withoutGlobalScopes()->count());
        $this->assertSame(15, ProductoImagen::withoutGlobalScopes()->count());
    }

    public function test_repara_textos_con_doble_codificacion_sin_tocar_los_correctos(): void
    {
        $this->assertSame('ó', LimpiezaTextos::reparar('Ã³'));
        $this->assertSame('—', LimpiezaTextos::reparar('â€”'));
        $this->assertSame('🔔', LimpiezaTextos::reparar('ðŸ””'));
        $this->assertSame("í\u{00AD}", LimpiezaTextos::reparar("íÂ\u{00AD}"));

        $correcto = 'Ñandú, ¿qué pasó? “Hola” — 50 % · ¡sí! Cliente: María';
        $this->assertSame($correcto, LimpiezaTextos::reparar($correcto));
    }
}
