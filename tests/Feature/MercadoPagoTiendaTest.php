<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Tenant;
use App\Support\AjustesNegocio;
use App\Support\TiendaConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Pago con tarjeta en la tienda con Mercado Pago (simulado con Http::fake). */
class MercadoPagoTiendaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $t;
    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->t = Tenant::create(['nombre_negocio' => 'Bodega Sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $this->t->slug = 'bodega-sol'; $this->t->save();
        AjustesNegocio::guardar($this->t->id, ['tienda_pagos' => ['efectivo', 'mercadopago']]);
        $this->pedido = Pedido::withoutGlobalScopes()->create(['tenant_id' => $this->t->id, 'numero' => 'T-1-MPAG', 'canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'recojo', 'subtotal' => 40, 'total' => 40, 'metodo_pago' => 'mercadopago', 'pagado' => false]);
    }

    public function test_sin_token_no_se_ofrece_la_tarjeta(): void
    {
        $neg = DB::table('tenants')->find($this->t->id);
        $this->assertSame(['efectivo'], array_column(TiendaConfig::de($neg)['pagos'], 'clave'));
    }

    public function test_paga_con_tarjeta_y_se_verifica_contra_mercado_pago(): void
    {
        AjustesNegocio::guardar($this->t->id, ['mp_access_token' => Crypt::encryptString('TEST-123-abc')]);
        $neg = DB::table('tenants')->find($this->t->id);
        $this->assertContains('mercadopago', array_column(TiendaConfig::de($neg)['pagos'], 'clave'));

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://mp/real', 'sandbox_init_point' => 'https://mp/sandbox'], 201),
            'api.mercadopago.com/v1/payments/999' => Http::response(['status' => 'approved', 'external_reference' => $this->t->id . ':' . $this->pedido->id, 'transaction_amount' => 40]),
            'api.mercadopago.com/v1/payments/555' => Http::response(['status' => 'approved', 'external_reference' => '777:' . $this->pedido->id, 'transaction_amount' => 40]),
        ]);

        $this->get(route('tienda.gracias', ['bodega-sol', 'T-1-MPAG']))->assertOk()->assertSee('Paga con tarjeta');
        $this->get(route('tienda.mp.pagar', ['bodega-sol', 'T-1-MPAG']))->assertRedirect('https://mp/sandbox');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'preferences') && $r['external_reference'] === $this->t->id . ':' . $this->pedido->id && $r->hasHeader('Authorization', 'Bearer TEST-123-abc'));

        // Un pago de otro negocio no sirve
        $this->get(route('tienda.gracias', ['bodega-sol', 'T-1-MPAG']) . '?payment_id=555&status=approved');
        $this->assertFalse((bool) $this->pedido->fresh()->pagado);

        // El aviso (webhook) confirma consultando a Mercado Pago
        $this->postJson('/api/pagos/mercadopago/webhook?negocio=' . $this->t->id, ['type' => 'payment', 'data' => ['id' => '999']])->assertOk();
        $p = $this->pedido->fresh();
        $this->assertTrue((bool) $p->pagado);
        $this->assertSame('MP 999', $p->pago_ref);
        $this->get(route('tienda.gracias', ['bodega-sol', 'T-1-MPAG']))->assertSee('Tu pago está confirmado');
    }
}
