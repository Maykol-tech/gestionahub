<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Arreglos de la guía: QR de todas las mesas, ingresos con concepto, pagos sin carritos repetidos, filtro por canal. */
class MejorasGuiaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $t = Tenant::create(['nombre_negocio' => 'Cevichería Sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    public function test_hoja_con_qr_de_todas_las_mesas(): void
    {
        Mesa::create(['tenant_id' => $this->admin->tenant_id, 'nombre' => 'Mesa A', 'capacidad' => 4]);
        Mesa::create(['tenant_id' => $this->admin->tenant_id, 'nombre' => 'Mesa B', 'capacidad' => 2]);
        $html = $this->actingAs($this->admin)->get(route('admin.mesas.qr_imprimir'))->assertOk()->assertSee('Mesa A')->assertSee('Mesa B')->getContent();
        $this->assertSame(2, substr_count($html, '<svg'));
        $this->assertStringNotContainsString('<?xml', $html);
        $this->actingAs($this->admin)->get(route('admin.mesas'))->assertSee('Imprimir todos los QR');
    }

    public function test_ingresos_muestra_fecha_y_concepto(): void
    {
        DB::table('ingresos_manuales')->insert(['tenant_id' => $this->admin->tenant_id, 'fecha' => now()->toDateString(), 'concepto' => 'Alquiler de toldo', 'monto' => 50, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin)->get(route('admin.ingresos'))->assertOk()->assertSee('Alquiler de toldo')->assertSee(now()->format('d/m/Y'));
    }

    public function test_pagos_ya_no_repite_carritos_y_pedidos_filtra_por_canal(): void
    {
        $this->actingAs($this->admin)->get(route('admin.pagos'))->assertOk()->assertSee('Pagos por revisar')->assertDontSee('Recordar por WhatsApp');
        $this->actingAs($this->admin)->get(route('admin.pedidos'))->assertOk()->assertSee('Todos los canales');
        $this->actingAs($this->admin)->get(route('admin.variantes'))->assertOk()->assertSee('Buscar producto');
    }

    public function test_los_errores_de_validacion_salen_en_espanol(): void
    {
        $this->actingAs($this->admin)->post(route('admin.mesas.guardar'), ['nombre' => '', 'capacidad' => 99]);
        $errores = session('errors');
        $this->assertSame('Completa el nombre.', $errores->first('nombre'));
        $this->assertSame('La capacidad no puede ser mayor que 50.', $errores->first('capacidad'));
    }
}
