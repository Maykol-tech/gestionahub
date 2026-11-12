<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\Sucursal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-46 (QR por mesa), GEST-47 (comandas a cocina) y GEST-48 (sucursales). */
class Bloque2SucursalesMesasCocinaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'cajero'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->tenant = Tenant::create(['nombre_negocio' => 'Cadena Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => 'b2@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    /** Asigna al negocio un plan con el limite de sucursales indicado. */
    private function plan(int $maxSucursales): void
    {
        $id = DB::table('planes_saas')->insertGetId([
            'nombre' => 'Plan ' . $maxSucursales, 'slug' => 'negocio', 'precio_mensual' => 89, 'precio_anual' => 890,
            'max_usuarios' => 5, 'max_sucursales' => $maxSucursales, 'activo' => true,
        ]);
        $this->tenant->update(['plan_id' => $id]);
    }

    public function test_sucursales_respeta_el_limite_del_plan(): void
    {
        $this->plan(2); // sede principal + 1 sucursal

        $this->actingAs($this->admin)->post(route('admin.sucursales.guardar'), [
            'nombre' => 'San Isidro', 'direccion' => 'Av. Javier Prado 100', 'telefono' => '014445555', 'horario' => 'Lun a Dom 8 a 22',
        ])->assertSessionHas('ok');

        $this->assertSame(1, Sucursal::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());

        $this->actingAs($this->admin)->post(route('admin.sucursales.guardar'), ['nombre' => 'Surco'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Sucursal::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_no_permite_nombres_repetidos_y_permite_editar_y_desactivar(): void
    {
        $this->plan(3);
        $this->actingAs($this->admin)->post(route('admin.sucursales.guardar'), ['nombre' => 'Centro']);
        $this->actingAs($this->admin)->post(route('admin.sucursales.guardar'), ['nombre' => 'Centro'])
            ->assertSessionHasErrors('nombre');

        $s = Sucursal::withoutGlobalScopes()->where('nombre', 'Centro')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.sucursales.actualizar', $s->id), [
            'nombre' => 'Centro Histórico', 'direccion' => 'Jr. Unión 300', 'activo' => '1',
        ])->assertSessionHas('ok');
        $this->assertSame('Centro Histórico', $s->fresh()->nombre);

        $this->actingAs($this->admin)->patch(route('admin.sucursales.estado', $s->id));
        $this->assertFalse($s->fresh()->activo);
    }

    public function test_eliminar_sucursal_y_no_ver_sucursales_de_otro_negocio(): void
    {
        $this->plan(3);
        $otro = Tenant::create(['nombre_negocio' => 'Otro', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $ajena = Sucursal::withoutGlobalScopes()->create(['tenant_id' => $otro->id, 'nombre' => 'Ajena', 'activo' => true]);

        $this->actingAs($this->admin)->delete(route('admin.sucursales.eliminar', $ajena->id))->assertNotFound();
        $this->assertNotNull(Sucursal::withoutGlobalScopes()->find($ajena->id));

        $this->actingAs($this->admin)->post(route('admin.sucursales.guardar'), ['nombre' => 'Propia']);
        $propia = Sucursal::withoutGlobalScopes()->where('nombre', 'Propia')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.sucursales.eliminar', $propia->id))->assertSessionHas('ok');
        $this->assertNull(Sucursal::withoutGlobalScopes()->find($propia->id));
    }

    public function test_la_pagina_de_sucursales_muestra_el_boton_de_agregar(): void
    {
        $this->plan(2);
        $this->actingAs($this->admin)->get(route('admin.modulo', 'sucursales'))
            ->assertOk()
            ->assertSee('Agregar sucursal')
            ->assertSee(route('admin.sucursales.guardar'), false);
    }

    public function test_qr_de_la_mesa_se_genera_en_el_servidor_y_apunta_a_su_url(): void
    {
        $mesa = Mesa::create(['tenant_id' => $this->tenant->id, 'nombre' => 'Mesa 1', 'capacidad' => 4]);

        $respuesta = $this->actingAs($this->admin)->get(route('admin.mesas.qr', $mesa->id));
        $respuesta->assertOk();
        $this->assertStringStartsWith('image/svg+xml', $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $respuesta->getContent());

        $descarga = $this->actingAs($this->admin)->get(route('admin.mesas.qr', ['mesa' => $mesa->id, 'descargar' => 1]));
        $this->assertStringContainsString('attachment; filename="qr-mesa-1.svg"', $descarga->headers->get('Content-Disposition'));

        $this->actingAs($this->admin)->get(route('admin.mesas'))
            ->assertOk()
            ->assertDontSee('api.qrserver.com')
            ->assertSee(route('admin.mesas.qr', $mesa->id), false);
    }

    public function test_cocina_devuelve_fragmento_con_comandas_en_curso(): void
    {
        $mesa = Mesa::create(['tenant_id' => $this->tenant->id, 'nombre' => 'Mesa 7', 'capacidad' => 2]);
        Comanda::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id, 'mesa_id' => $mesa->id, 'estado' => 'pendiente',
            'items' => [['nombre' => 'Lomo saltado', 'cantidad' => 2]], 'total' => 50,
        ]);
        Comanda::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id, 'mesa_id' => $mesa->id, 'estado' => 'cobrada',
            'items' => [['nombre' => 'Ya cobrado', 'cantidad' => 1]], 'total' => 10,
        ]);

        $fragmento = $this->actingAs($this->admin)->get(route('admin.cocina.fragmento'));
        $fragmento->assertOk()->assertSee('Lomo saltado')->assertDontSee('Ya cobrado')->assertDontSee('<html', false);

        $this->actingAs($this->admin)->get(route('admin.cocina'))
            ->assertOk()
            ->assertSee('id="cocina-grid"', false)
            ->assertSee(route('admin.cocina.fragmento'), false);
    }

    public function test_cajero_no_puede_gestionar_sucursales(): void
    {
        $this->plan(3);
        $cajero = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Caja', 'email' => 'caja-b2@test.pe', 'password' => 'Secreto123!']);
        $cajero->assignRole('cajero');

        $this->actingAs($cajero)->post(route('admin.sucursales.guardar'), ['nombre' => 'Intruso'])->assertForbidden();
        $this->assertSame(0, Sucursal::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
    }
}
