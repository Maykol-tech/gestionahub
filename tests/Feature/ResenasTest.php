<?php

namespace Tests\Feature;

use App\Models\Resena;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenasTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_las_publicadas_aparecen_en_la_pagina_publica(): void
    {
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        Resena::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'cliente_nombre' => 'Ana', 'calificacion' => 5, 'estado' => 'publicada',
        ]);
        Resena::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'cliente_nombre' => 'Luis', 'calificacion' => 1, 'estado' => 'pendiente',
        ]);

        $publicadas = Resena::withoutGlobalScopes()->where('tenant_id', $tenant->id)->publicadas()->count();

        $this->assertSame(1, $publicadas);
    }

    public function test_una_resena_nueva_entra_como_pendiente(): void
    {
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        \Illuminate\Support\Facades\DB::table('tenants')->where('id', $tenant->id)->update(['slug' => 'prueba']);

        $this->post(route('publico.resenas.enviar', ['slug' => 'prueba']), [
            'cliente_nombre' => 'Ana', 'calificacion' => 4,
        ]);

        $this->assertDatabaseMissing('resenas', ['estado' => 'publicada']);
    }
}
