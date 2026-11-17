<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Productos sin foto real: se marcan y la primera foto subida reemplaza a la de ejemplo. */
class ProductosSinFotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_foto_real_reemplaza_a_la_de_ejemplo(): void
    {
        Storage::fake('public');
        $t = Tenant::create(['nombre_negocio' => 'Bodega', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'A', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');
        $p = Producto::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Gaseosa', 'precio' => 3, 'stock' => 10, 'activo' => true]);
        ProductoImagen::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'producto_id' => $p->id, 'ruta' => 'demo/productos/p02.png', 'orden' => 1, 'es_principal' => true]);
        $p->sincronizarImagenPrincipal();

        $lista = $this->actingAs($u)->getJson(route('admin.productos.lista'))->json('data');
        $this->assertFalse($lista[0]['foto_real']);

        $r = $this->actingAs($u)->post(route('admin.productos.imagenes', $p->id), ['imagenes' => [UploadedFile::fake()->image('foto.jpg', 400, 400)]], ['Accept' => 'application/json'])
            ->assertOk();
        $this->assertTrue($r->json('data.foto_real'));
        $this->assertStringNotContainsString('demo/', (string) $p->fresh()->imagen_url);
    }
}
