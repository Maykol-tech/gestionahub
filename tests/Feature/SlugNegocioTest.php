<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlugNegocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_negocio_sin_slug_recibe_uno_y_su_tienda_abre(): void
    {
        $this->seed();
        $id = DB::table('tenants')->value('id');
        DB::table('tenants')->where('id', $id)->update(['slug' => null, 'nombre_negocio' => 'El Clasico', 'estado' => 'activo']);

        (require database_path('migrations/2026_11_25_090000_completar_slug_negocios.php'))->up();

        $this->assertSame('el-clasico', DB::table('tenants')->where('id', $id)->value('slug'));
        $this->get('/tienda/el-clasico')->assertOk();
    }
}
