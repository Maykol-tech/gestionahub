<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Una tabla "sucursales" antigua sin "activo" recibe las columnas que faltan sin perder datos. */
class SucursalesColumnasTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrega_columnas_faltantes_y_respeta_activa(): void
    {
        Schema::drop('sucursales');
        Schema::create('sucursales', function ($t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
            $t->boolean('activa')->default(true);
        });
        DB::table('sucursales')->insert([['tenant_id' => 1, 'nombre' => 'Centro', 'activa' => true], ['tenant_id' => 1, 'nombre' => 'Norte', 'activa' => false]]);

        (require database_path('migrations/2026_11_13_100000_sucursales_columnas_faltantes.php'))->up();

        foreach (['activo', 'direccion', 'telefono', 'email', 'horario', 'created_at'] as $c) {
            $this->assertTrue(Schema::hasColumn('sucursales', $c), $c);
        }
        $this->assertSame(['Centro' => 1, 'Norte' => 0], DB::table('sucursales')->pluck('activo', 'nombre')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(1, \App\Models\Sucursal::query()->where('activo', true)->count());

        // Correrla otra vez no hace nada
        (require database_path('migrations/2026_11_13_100000_sucursales_columnas_faltantes.php'))->up();
        $this->assertSame(2, DB::table('sucursales')->count());
    }

    public function test_revisar_esquema_lista_lo_que_falta(): void
    {
        $this->artisan('esquema:revisar')->assertSuccessful();
        Schema::table('sucursales', fn ($t) => $t->dropColumn('horario'));
        $this->artisan('esquema:revisar')->expectsOutputToContain('Faltan 1')->assertFailed();
    }

    public function test_repara_columnas_faltantes_de_cualquier_tabla(): void
    {
        Schema::drop('proveedores');
        Schema::create('proveedores', function ($t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
        });
        DB::table('proveedores')->insert(['tenant_id' => 1, 'nombre' => 'Antiguo']);
        $this->artisan('esquema:revisar')->assertFailed();

        (require database_path('migrations/2026_11_13_110000_reparar_columnas_faltantes.php'))->up();

        $this->artisan('esquema:revisar')->assertSuccessful();
        $this->assertSame('Antiguo', DB::table('proveedores')->value('nombre'));
        DB::table('proveedores')->insert(['tenant_id' => 1, 'nombre' => 'Nuevo', 'contacto' => 'Carlos', 'condicion_pago' => 'contado', 'activo' => true]);
        $this->assertSame('Carlos', DB::table('proveedores')->where('nombre', 'Nuevo')->value('contacto'));
    }

    public function test_copia_datos_de_columnas_con_nombre_antiguo(): void
    {
        Schema::drop('repartidores');
        Schema::create('repartidores', function ($t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre_completo');
            $t->boolean('activo')->default(true);
        });
        DB::table('repartidores')->insert(['tenant_id' => 1, 'nombre_completo' => 'Juan Quispe']);
        Schema::drop('campanas_publicidad');
        Schema::create('campanas_publicidad', function ($t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->string('nombre');
            $t->string('plataforma')->nullable();
            $t->boolean('activa')->default(true);
        });
        DB::table('campanas_publicidad')->insert(['tenant_id' => 1, 'nombre' => 'Promo', 'plataforma' => 'tiktok', 'activa' => false]);

        (require database_path('migrations/2026_11_13_110000_reparar_columnas_faltantes.php'))->up();

        $this->assertSame('Juan Quispe', DB::table('repartidores')->value('nombre'));
        $this->assertTrue((bool) DB::table('repartidores')->value('disponible'));
        $c = DB::table('campanas_publicidad')->first();
        $this->assertSame('tiktok', $c->canal);
        $this->assertFalse((bool) $c->activo);
    }
}
