<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\PlanCliente;
use App\Models\Tenant;
use App\Support\AjustesNegocio;
use App\Support\Membresias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Formulario de tarjeta de Mercado Pago dentro de la tienda y de la membresía (simulado con Http::fake). */
class MercadoPagoFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $t;
    private Pedido $pedido;

    private const FORM = ['token' => 'tok_123', 'payment_method_id' => 'visa', 'installments' => 1, 'issuer_id' => '1',
        'payer' => ['email' => 'ana@correo.pe', 'identification' => ['type' => 'DNI', 'number' => '45871236']], 'transaction_amount' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        $this->t = Tenant::create(['nombre_negocio' => 'Bodega Sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $this->t->slug = 'bodega-sol'; $this->t->save();
        AjustesNegocio::guardar($this->t->id, ['tienda_pagos' => ['efectivo', 'mercadopago'], 'mp_access_token' => Crypt::encryptString('TEST-123-abc')]);
        $this->pedido = Pedido::withoutGlobalScopes()->create(['tenant_id' => $this->t->id, 'numero' => 'T-1-MPAG', 'canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'recojo', 'subtotal' => 40, 'total' => 40, 'metodo_pago' => 'mercadopago', 'pagado' => false]);
    }

    private function pago(string $id, string $estado, float $monto, string $ref, string $detalle = 'accredited'): array
    {
        return ['id' => (int) $id, 'status' => $estado, 'status_detail' => $detalle, 'external_reference' => $ref, 'transaction_amount' => $monto, 'card' => ['last_four_digits' => '3704'], 'payment_method_id' => 'visa'];
    }

    public function test_sin_public_key_se_mantiene_el_boton(): void
    {
        $this->get(route('tienda.gracias', ['bodega-sol', 'T-1-MPAG']))->assertOk()->assertSee('Pagar S/ 40.00 con tarjeta')->assertDontSee('sdk.mercadopago.com');
        $this->postJson(route('tienda.mp.cobrar', ['bodega-sol', 'T-1-MPAG']), self::FORM)->assertStatus(422);
    }

    public function test_paga_dentro_de_la_tienda_con_el_monto_del_servidor(): void
    {
        AjustesNegocio::guardar($this->t->id, ['mp_public_key' => 'TEST-pub-999']);
        $ref = $this->t->id . ':' . $this->pedido->id;
        Http::fake([
            'api.mercadopago.com/v1/payments/777' => Http::response($this->pago('777', 'approved', 40, $ref)),
            'api.mercadopago.com/v1/payments' => Http::response($this->pago('777', 'approved', 40, $ref), 201),
        ]);

        $this->get(route('tienda.gracias', ['bodega-sol', 'T-1-MPAG']))->assertSee('sdk.mercadopago.com')->assertSee('TEST-pub-999')->assertSee('csrf-token', false);

        $this->postJson(route('tienda.mp.cobrar', ['bodega-sol', 'T-1-MPAG']), self::FORM)->assertOk()
            ->assertJsonPath('estado', 'approved')->assertJsonPath('tarjeta', '3704');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v1/payments')
            && $r['transaction_amount'] == 40 && $r['token'] === 'tok_123' && $r['external_reference'] === $ref
            && $r['payer']['identification']['number'] === '45871236' && $r->hasHeader('X-Idempotency-Key'));
        $this->assertTrue((bool) $this->pedido->fresh()->pagado);
        $this->assertSame('MP 777', $this->pedido->fresh()->pago_ref);

        // Ya pagado: no se vuelve a cobrar
        Http::fake();
        $this->postJson(route('tienda.mp.cobrar', ['bodega-sol', 'T-1-MPAG']), self::FORM)->assertJsonPath('estado', 'approved');
        Http::assertNothingSent();
    }

    public function test_rechazo_muestra_el_motivo_en_espanol(): void
    {
        AjustesNegocio::guardar($this->t->id, ['mp_public_key' => 'TEST-pub-999']);
        Http::fake(['api.mercadopago.com/v1/payments' => Http::response($this->pago('5', 'rejected', 40, 'x', 'cc_rejected_insufficient_amount'), 201)]);

        $this->postJson(route('tienda.mp.cobrar', ['bodega-sol', 'T-1-MPAG']), self::FORM)->assertOk()
            ->assertJsonPath('estado', 'rejected')->assertJsonPath('mensaje', 'La tarjeta no tiene fondos suficientes. Prueba con otra tarjeta o con Yape.');
        $this->assertFalse((bool) $this->pedido->fresh()->pagado);

        $this->postJson(route('tienda.mp.cobrar', ['bodega-sol', 'T-1-MPAG']), ['token' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['payment_method_id', 'payer.email']);
    }

    public function test_membresia_se_paga_dentro_de_su_pagina(): void
    {
        AjustesNegocio::guardar($this->t->id, ['mp_public_key' => 'TEST-pub-999']);
        $plan = PlanCliente::withoutGlobalScopes()->create(['tenant_id' => $this->t->id, 'nombre' => 'Mensual', 'precio' => 80, 'periodicidad' => 'mensual', 'activo' => true]);
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $this->t->id, 'nombre' => 'Pedro', 'telefono' => '933222111', 'activo' => true]);
        $s = Membresias::suscribir($plan, $c);
        $cobro = $s->cobros()->first();
        $ref = $this->t->id . ':m:' . $cobro->id;
        Http::fake([
            'api.mercadopago.com/v1/payments/88' => Http::response($this->pago('88', 'approved', 80, $ref)),
            'api.mercadopago.com/v1/payments' => Http::response($this->pago('88', 'approved', 80, $ref), 201),
        ]);

        $this->get(route('membresia.publica', $s->token))->assertSee('sdk.mercadopago.com');
        $this->postJson(route('membresia.tarjeta.cobrar', $s->token), self::FORM)->assertOk()->assertJsonPath('estado', 'approved');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v1/payments') && $r['transaction_amount'] == 80);
        $this->assertSame('pagado', $cobro->fresh()->estado);
        $this->assertSame('activa', $s->fresh()->estado);
    }
}
