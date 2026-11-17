<?php

namespace Tests\Feature;

use App\Models\PagoManual;
use App\Models\Pedido;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AjustesNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** El cliente sube la captura de su Yape desde la tienda y el negocio la aprueba en Pagos por revisar. */
class PagoCapturaTiendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_sube_captura_y_el_negocio_confirma(): void
    {
        Storage::fake('local');
        $t = Tenant::create(['nombre_negocio' => 'Bodega Sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $t->slug = 'bodega-sol'; $t->save();
        AjustesNegocio::guardar($t->id, ['tienda_pagos' => ['yape', 'efectivo'], 'yape_numero' => '987654321']);
        $pedido = Pedido::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'numero' => 'T-261112-ABCD', 'canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'recojo',
            'subtotal' => 30, 'total' => 30, 'metodo_pago' => 'yape', 'pagado' => false, 'cliente_nombre' => 'Lucía']);

        $this->get(route('tienda.gracias', ['bodega-sol', 'T-261112-ABCD']))->assertOk()->assertSee('Enviar captura');
        $this->post(route('tienda.pago.reportar', ['bodega-sol', 'T-261112-ABCD']), ['captura' => UploadedFile::fake()->image('yape.jpg'), 'referencia' => '123456'])
            ->assertRedirect(route('tienda.gracias', ['bodega-sol', 'T-261112-ABCD']));
        $this->get(route('tienda.gracias', ['bodega-sol', 'T-261112-ABCD']))->assertSee('Recibimos tu captura');

        // Volver a subir reemplaza, no duplica
        $this->post(route('tienda.pago.reportar', ['bodega-sol', 'T-261112-ABCD']), ['captura' => UploadedFile::fake()->image('yape2.jpg')]);
        $pago = PagoManual::withoutGlobalScopes()->sole();
        $this->assertSame('tienda', $pago->origen);
        $this->assertSame('123456', $pago->referencia);
        Storage::disk('local')->assertExists($pago->captura);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.pagos'))->assertOk()->assertSee('Lo envió el cliente')->assertSee('T-261112-ABCD');
        $this->actingAs($admin)->get(route('admin.pagos.captura', $pago->id))->assertOk();
        $this->actingAs($admin)->post(route('admin.pagos.confirmar', $pago->id))->assertSessionHas('ok');
        $this->assertTrue((bool) $pedido->fresh()->pagado);
        auth()->logout();

        $this->get(route('tienda.gracias', ['bodega-sol', 'T-261112-ABCD']))->assertSee('Tu pago está confirmado');
        $this->post(route('tienda.pago.reportar', ['bodega-sol', 'T-261112-ABCD']), ['captura' => UploadedFile::fake()->image('otra.jpg')])->assertStatus(422);
    }

    public function test_exige_imagen_y_no_acepta_pedidos_en_efectivo(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega Luna', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $t->slug = 'bodega-luna'; $t->save();
        Pedido::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'numero' => 'T-1-EFEC', 'canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'recojo', 'subtotal' => 9, 'total' => 9, 'metodo_pago' => 'efectivo']);
        Pedido::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'numero' => 'T-1-YAPE', 'canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'recojo', 'subtotal' => 9, 'total' => 9, 'metodo_pago' => 'yape']);
        $this->post(route('tienda.pago.reportar', ['bodega-luna', 'T-1-EFEC']), ['captura' => UploadedFile::fake()->image('a.jpg')])->assertStatus(422);
        $this->post(route('tienda.pago.reportar', ['bodega-luna', 'T-1-YAPE']), ['captura' => UploadedFile::fake()->create('a.pdf', 10)])->assertSessionHasErrors('captura');
    }
}
