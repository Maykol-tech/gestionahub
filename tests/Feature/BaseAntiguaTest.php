<?php

namespace Tests\Feature;

use App\Models\Repartidor;
use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Bases de una versión antigua: la columna obligatoria repartidores.nombre_completo se llena sola. */
class BaseAntiguaTest extends TestCase
{
    use RefreshDatabase;

    public function test_repartidor_llena_la_columna_antigua_nombre_completo(): void
    {
        Schema::table('repartidores', fn (Blueprint $t) => $t->string('nombre_completo')->nullable());
        $t = Tenant::create(['nombre_negocio' => 'Antigua', 'slug' => 'antigua', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);

        $r = Repartidor::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Repartidor Demo', 'telefono' => '900000001', 'vehiculo' => 'moto', 'activo' => true, 'disponible' => true]);

        $this->assertSame('Repartidor Demo', DB::table('repartidores')->where('id', $r->id)->value('nombre_completo'));
    }

    public function test_columnas_antiguas_obligatorias_sin_valor_se_llenan_solas(): void
    {
        // Simula la tabla de una base antigua: columnas obligatorias que el programa ya no conoce
        Schema::drop('repartidores');
        Schema::create('repartidores', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('nombre');
            $t->string('nombre_completo');
            $t->string('tipo_documento');
            $t->string('numero_documento');
            $t->string('email')->unique();
            $t->integer('calificacion');
            $t->date('fecha_ingreso');
            $t->string('telefono')->nullable();
            $t->string('dni')->nullable();
            $t->string('vehiculo')->default('moto');
            $t->boolean('activo')->default(true);
            $t->boolean('disponible')->default(true);
            $t->string('token')->nullable();
            $t->string('placa')->nullable();
            $t->decimal('lat_actual', 10, 7)->nullable();
            $t->decimal('lng_actual', 10, 7)->nullable();
            $t->dateTime('updated_location_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        \App\Support\ColumnasLegado::olvidar();
        $t = Tenant::create(['nombre_negocio' => 'Antigua 2', 'slug' => 'antigua-2', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);

        $r = Repartidor::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Repartidor Demo', 'telefono' => '900000001', 'vehiculo' => 'moto', 'activo' => true, 'disponible' => true]);

        $fila = DB::table('repartidores')->where('id', $r->id)->first();
        $this->assertSame('Repartidor Demo', $fila->nombre_completo);
        $this->assertSame('', $fila->tipo_documento);
        $this->assertEquals(0, $fila->calificacion);
        $this->assertNotEmpty($fila->fecha_ingreso);
        // email único: un segundo repartidor no choca con el primero
        Repartidor::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Otro', 'telefono' => '900000003', 'vehiculo' => 'moto', 'activo' => true, 'disponible' => true]);
        $this->assertSame(2, DB::table('repartidores')->distinct()->count('email'));
    }

    public function test_en_una_base_nueva_no_hace_nada(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Nueva', 'slug' => 'nueva', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Repartidor::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Ana', 'telefono' => '900000002', 'vehiculo' => 'moto', 'activo' => true, 'disponible' => true]);

        $this->assertFalse(Schema::hasColumn('repartidores', 'nombre_completo'));
        $this->assertSame(1, Repartidor::withoutGlobalScopes()->count());
    }
}
