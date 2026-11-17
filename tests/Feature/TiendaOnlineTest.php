<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\FormaEntrega;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoKilo;
use App\Models\ProductoVariante;
use App\Models\Tenant;
use App\Models\TiendaContenido;
use App\Models\User;
use App\Support\AjustesNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-101..112: tienda online (catálogo, checkout, pedidos programados, cupones, peso real y SEO). */
class TiendaOnlineTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Producto $polo;

    private Producto $pollo;

    private Producto $queso;

    private FormaEntrega $delivery;

    protected function setUp(): void
    {
        parent::setUp();
        // Miércoles 11 de noviembre de 2026, 12:00 (abierto con el horario por defecto 9 a 19)
        $this->travelTo(Carbon::parse('2026-11-11 12:00'));
        $this->tenant = Tenant::create(['nombre_negocio' => 'Bodega Sol', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->tenant->slug = 'bodega-sol';
        $this->tenant->save();
        $t = $this->tenant->id;

        $this->polo = Producto::withoutGlobalScopes()->create(['tenant_id' => $t, 'nombre' => 'Agua', 'slug' => 'agua', 'precio' => 5, 'stock' => 10, 'stock_minimo' => 0, 'activo' => true, 'descripcion' => 'Agua sin gas 625 ml']);
        $this->pollo = Producto::withoutGlobalScopes()->create(['tenant_id' => $t, 'nombre' => 'Pollo a la brasa', 'slug' => 'pollo-a-la-brasa', 'precio' => 20, 'stock' => 50, 'stock_minimo' => 0, 'activo' => true, 'tiene_variantes' => true]);
        ProductoVariante::withoutGlobalScopes()->create(['tenant_id' => $t, 'producto_id' => $this->pollo->id, 'nombre' => '1/4', 'precio' => 20, 'stock' => 5, 'activo' => true]);
        ProductoVariante::withoutGlobalScopes()->create(['tenant_id' => $t, 'producto_id' => $this->pollo->id, 'nombre' => 'Entero', 'precio' => 70, 'stock' => 2, 'activo' => true]);
        $this->queso = Producto::withoutGlobalScopes()->create(['tenant_id' => $t, 'nombre' => 'Queso fresco', 'slug' => 'queso-fresco', 'precio' => 30, 'stock' => 0, 'stock_minimo' => 0, 'activo' => true]);
        ProductoKilo::withoutGlobalScopes()->create(['tenant_id' => $t, 'producto_id' => $this->queso->id, 'precio_kg' => 30, 'activo' => true, 'presentaciones' => [0.25, 0.5, 1], 'peso_libre' => true, 'peso_minimo' => 0.1]);

        $this->delivery = FormaEntrega::withoutGlobalScopes()->create(['tenant_id' => $t, 'nombre' => 'Delivery', 'tipo' => 'delivery', 'costo' => 8, 'activa' => true, 'monto_minimo' => 10, 'gratis_desde' => 100]);
    }

    private function variante(string $nombre): ProductoVariante
    {
        return ProductoVariante::withoutGlobalScopes()->where('producto_id', $this->pollo->id)->where('nombre', $nombre)->first();
    }

    private function pedir(array $extra = [])
    {
        return $this->postJson(route('tienda.pedido', 'bodega-sol'), $extra + [
            'carrito' => [
                ['producto_id' => $this->polo->id, 'cantidad' => 2],
                ['producto_id' => $this->pollo->id, 'variante_id' => $this->variante('1/4')->id, 'cantidad' => 1],
                ['producto_id' => $this->queso->id, 'cantidad' => 1, 'peso' => 0.5],
            ],
            'nombre' => 'Ana Ruiz', 'telefono' => '987654321', 'entrega_id' => $this->delivery->id,
            'direccion' => 'Av. Arequipa 123', 'metodo_pago' => 'yape',
        ]);
    }

    public function test_tienda_muestra_catalogo_con_variantes_kilo_y_seo(): void
    {
        $this->get(route('tienda.publica', 'bodega-sol'))->assertOk()
            ->assertSee('Bodega Sol')->assertSee('Abierto ahora')->assertSee('"@type":"Store"', false)
            ->assertSee('og:title', false)->assertSee('Pollo a la brasa');

        $this->get(route('tienda.producto', ['bodega-sol', 'pollo-a-la-brasa']))->assertOk()
            ->assertSee('"@type":"Product"', false)->assertSee('<link rel="canonical" href="' . route('tienda.producto', ['bodega-sol', 'pollo-a-la-brasa']) . '">', false);
        $this->get(route('tienda.producto', ['bodega-sol', 'no-existe']))->assertNotFound();
        $this->get(route('tienda.publica', 'no-existe'))->assertNotFound();
    }

    public function test_cotizar_aplica_envio_minimo_cupon_y_puntos(): void
    {
        DB::table('cupones_tienda')->insert(['tenant_id' => $this->tenant->id, 'codigo' => 'HOLA10', 'tipo' => 'porcentaje', 'valor' => 10, 'minimo' => 0, 'usos' => 0, 'activo' => true]);
        $carrito = [['producto_id' => $this->polo->id, 'cantidad' => 2], ['producto_id' => $this->queso->id, 'cantidad' => 1, 'peso' => 0.5]];

        $this->postJson(route('tienda.cotizar', 'bodega-sol'), ['carrito' => $carrito, 'entrega_id' => $this->delivery->id])
            ->assertOk()->assertJsonPath('subtotal', 25)->assertJsonPath('envio', 8)->assertJsonPath('total', 33);

        $this->postJson(route('tienda.cotizar', 'bodega-sol'), ['carrito' => $carrito, 'entrega_id' => $this->delivery->id, 'cupon' => 'hola10'])
            ->assertOk()->assertJsonPath('desc_cupon', 2.5)->assertJsonPath('cupon.codigo', 'HOLA10')->assertJsonPath('total', 30.5);

        $this->postJson(route('tienda.cotizar', 'bodega-sol'), ['carrito' => $carrito, 'cupon' => 'NOEXISTE'])->assertStatus(422)->assertJsonValidationErrors('cupon');

        // Pedido mínimo del delivery: S/ 10
        $this->postJson(route('tienda.cotizar', 'bodega-sol'), ['carrito' => [['producto_id' => $this->polo->id, 'cantidad' => 1]], 'entrega_id' => $this->delivery->id])
            ->assertStatus(422);

        // Variante obligatoria y stock por variante
        $this->postJson(route('tienda.cotizar', 'bodega-sol'), ['carrito' => [['producto_id' => $this->pollo->id, 'cantidad' => 1]]])->assertStatus(422);
        $this->postJson(route('tienda.cotizar', 'bodega-sol'), ['carrito' => [['producto_id' => $this->pollo->id, 'variante_id' => $this->variante('Entero')->id, 'cantidad' => 3]]])->assertStatus(422);
    }

    public function test_pedido_descuenta_stock_crea_cliente_y_aparece_en_el_panel(): void
    {
        $r = $this->pedir()->assertOk()->assertJsonPath('ok', true);
        $pedido = Pedido::withoutGlobalScopes()->where('numero', $r->json('numero'))->firstOrFail();

        $this->assertSame('tienda', $pedido->canal);
        $this->assertSame('pendiente', $pedido->estado);
        $this->assertEquals(5 * 2 + 20 + 15, (float) $pedido->subtotal);
        $this->assertEquals(45 + 8, (float) $pedido->total);
        $this->assertSame('987654321', $pedido->cliente_telefono);
        $this->assertSame(8, (int) Producto::withoutGlobalScopes()->find($this->polo->id)->stock);
        $this->assertSame(4, (int) $this->variante('1/4')->stock);
        $this->assertNotNull(CrmCliente::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('telefono', '987654321')->first());
        $this->assertEquals(0.5, (float) PedidoItem::withoutGlobalScopes()->where('pedido_id', $pedido->id)->whereNotNull('peso_kg')->value('peso_kg'));

        $this->get($r->json('url'))->assertOk()->assertSee($pedido->numero)->assertSee('Paga con Yape');

        // GEST-108: aviso en la campana del panel
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->getJson(url('admin/notificaciones'))->assertOk()->assertSee('tienda online');
    }

    public function test_peso_real_recalcula_el_total(): void
    {
        $numero = $this->pedir()->json('numero');
        $pedido = Pedido::withoutGlobalScopes()->where('numero', $numero)->first();
        $item = PedidoItem::withoutGlobalScopes()->where('pedido_id', $pedido->id)->whereNotNull('peso_kg')->first();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');

        // Pidió 0.5 kg (S/ 15); se despacharon 0.6 kg → S/ 18, el total sube S/ 3
        $this->actingAs($admin)->patchJson(route('admin.pedidos.peso', $item->id), ['peso_real_kg' => 0.6])
            ->assertOk()->assertJsonPath('data.total', 56);
        $this->assertEquals(18, (float) $item->fresh()->subtotal);
        // Corregir otra vez parte del peso pedido, no se acumula
        $this->actingAs($admin)->patchJson(route('admin.pedidos.peso', $item->id), ['peso_real_kg' => 0.5])->assertOk()->assertJsonPath('data.total', 53);
    }

    public function test_cerrado_exige_programar_el_pedido(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 22:00'));
        $this->pedir()->assertStatus(422)->assertJsonValidationErrors('programado_para');
        $this->pedir(['programado_para' => '2026-11-11 23:30'])->assertStatus(422)->assertJsonValidationErrors('programado_para');

        $r = $this->pedir(['programado_para' => '2026-11-12 10:00'])->assertOk();
        $this->assertSame('2026-11-12 10:00', Pedido::withoutGlobalScopes()->where('numero', $r->json('numero'))->first()->programado_para->format('Y-m-d H:i'));
    }

    public function test_metodo_de_pago_debe_estar_habilitado(): void
    {
        AjustesNegocio::guardar($this->tenant->id, ['tienda_pagos' => ['efectivo']]);
        $this->pedir()->assertStatus(422)->assertJsonValidationErrors('metodo_pago');
        $this->pedir(['metodo_pago' => 'efectivo'])->assertOk();
    }

    public function test_paginas_sitemap_robots_y_config(): void
    {
        TiendaContenido::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'seccion' => 'paginas', 'contenido' => ['terminos' => 'Precios con IGV.', 'politicas' => '', 'faq' => [['pregunta' => '¿Hacen delivery?', 'respuesta' => 'Sí.']]]]);

        $this->get(route('tienda.pagina', ['bodega-sol', 'terminos']))->assertOk()->assertSee('Precios con IGV.');
        $this->get(route('tienda.pagina', ['bodega-sol', 'politicas']))->assertNotFound();
        $this->get(route('tienda.pagina', ['bodega-sol', 'preguntas']))->assertOk()->assertSee('FAQPage', false);

        $this->get(route('tienda.sitemap', 'bodega-sol'))->assertOk()
            ->assertSee(route('tienda.producto', ['bodega-sol', 'agua']), false)->assertSee('/p/terminos', false)->assertDontSee('/p/politicas', false);
        $this->get(route('tienda.robots', 'bodega-sol'))->assertOk()->assertSee('Sitemap: ' . route('tienda.sitemap', 'bodega-sol'), false);
        $this->getJson(route('publico.tienda.config', 'bodega-sol'))->assertOk()->assertJsonPath('negocio.nombre', 'Bodega Sol')->assertJsonPath('abierta', true);
    }

    public function test_editor_guarda_contenido_destacados_cupones_y_entregas(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.tienda.contenido'))->assertOk()->assertSee('Cupones');
        $this->actingAs($admin)->post(route('admin.tienda.contenido.guardar'), [
            'avisos' => "Delivery gratis\nNuevo local",
            'banners' => json_encode([['imagen' => 'https://cdn.test/b.jpg', 'titulo' => '2x1', 'texto' => '', 'enlace' => '#productos']]),
            'faq' => json_encode([['pregunta' => '¿Abren domingo?', 'respuesta' => 'Sí']]),
            'destacados_enviado' => 1, 'destacados' => [$this->polo->id],
            'tienda_pagos' => ['efectivo', 'transferencia'], 'yape_numero' => '987654321',
            'tienda_programar' => 1, 'tienda_puntos' => 0,
        ])->assertSessionHasNoErrors()->assertSessionHas('ok');

        $c = TiendaContenido::todo($this->tenant->id);
        $this->assertSame(['Delivery gratis', 'Nuevo local'], $c['avisos']['items']);
        $this->assertSame('2x1', $c['banners']['items'][0]['titulo']);
        $this->assertTrue((bool) Producto::withoutGlobalScopes()->find($this->polo->id)->destacado);
        $this->assertSame(['efectivo', 'transferencia'], AjustesNegocio::leer($this->tenant->id)['tienda_pagos']);
        $this->assertFalse(AjustesNegocio::leer($this->tenant->id)['tienda_puntos']);
        $this->assertSame('987654321', DB::table('tenants')->where('id', $this->tenant->id)->value('yape_numero'));

        $this->actingAs($admin)->post(route('admin.tienda.cupones.guardar'), ['codigo' => 'promo 5', 'tipo' => 'monto', 'valor' => 5])->assertSessionHas('ok');
        $this->assertSame('PROMO5', DB::table('cupones_tienda')->value('codigo'));
        $this->actingAs($admin)->post(route('admin.tienda.cupones.guardar'), ['codigo' => 'PROMO5', 'tipo' => 'monto', 'valor' => 5])->assertSessionHasErrors('codigo');

        $this->actingAs($admin)->put(route('admin.entregas.actualizar', $this->delivery->id), [
            'nombre' => 'Delivery Lima', 'tipo' => 'delivery', 'costo' => 6, 'monto_minimo' => 20, 'gratis_desde' => '', 'usa_zonas' => 1,
        ])->assertSessionHas('ok');
        $e = $this->delivery->fresh();
        $this->assertSame('Delivery Lima', $e->nombre);
        $this->assertTrue($e->usa_zonas);
        $this->assertNull($e->gratis_desde);
        $this->actingAs($admin)->get(route('admin.entregas'))->assertOk()->assertSee('Delivery Lima');
    }
}
