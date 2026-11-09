<?php

namespace Tests\Feature;

use App\Models\CampanaWhatsapp;
use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\Tenant;
use App\Models\WhatsappConexion;
use App\Support\Campanas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampanasProspectosTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
    }

    private function cliente(Tenant $t, string $telefono): CrmCliente
    {
        return CrmCliente::withoutGlobalScopes()->create([
            'tenant_id' => $t->id, 'nombre' => 'Cliente ' . $telefono, 'telefono' => $telefono, 'activo' => true,
        ]);
    }

    private function pedido(Tenant $t, CrmCliente $c, int $hacePocoDias): void
    {
        $p = Pedido::withoutGlobalScopes()->create([
            'tenant_id' => $t->id, 'cliente_id' => $c->id, 'numero' => 'P-' . random_int(1000, 9999), 'total' => 20,
        ]);
        $p->forceFill(['created_at' => now()->subDays($hacePocoDias)])->saveQuietly();
    }

    public function test_segmento_inactivos_incluye_a_quien_no_compra_hace_60_dias(): void
    {
        $t = $this->tenant();
        $activo = $this->cliente($t, '51911111111');
        $dormido = $this->cliente($t, '51922222222');
        $nuncaCompro = $this->cliente($t, '51933333333');

        $this->pedido($t, $activo, 5);
        $this->pedido($t, $dormido, 90);

        $telefonos = Campanas::destinatarios($t->id, 'inactivos')->all();

        $this->assertContains('51922222222', $telefonos);
        $this->assertContains('51933333333', $telefonos);
        $this->assertNotContains('51911111111', $telefonos);
    }

    public function test_segmento_frecuentes_requiere_tres_pedidos(): void
    {
        $t = $this->tenant();
        $fiel = $this->cliente($t, '51944444444');
        $nuevo = $this->cliente($t, '51955555555');

        foreach ([1, 2, 3] as $d) {
            $this->pedido($t, $fiel, $d);
        }
        $this->pedido($t, $nuevo, 1);

        $telefonos = Campanas::destinatarios($t->id, 'frecuentes')->all();

        $this->assertSame(['51944444444'], $telefonos);
    }

    public function test_enviar_campana_cuenta_entregados_y_fallidos(): void
    {
        $t = $this->tenant();
        $this->cliente($t, '51966666666');
        $this->cliente($t, '51977777777');

        $conexion = new WhatsappConexion(['phone_id' => '123', 'numero_visible' => '999', 'activo' => true]);
        $conexion->tenant_id = $t->id;
        $conexion->guardarToken('token');
        $conexion->save();

        Http::fakeSequence()->push(['messages' => [['id' => 'a']]], 200)->push(['error' => 'x'], 400);

        $campana = CampanaWhatsapp::withoutGlobalScopes()->create([
            'tenant_id' => $t->id, 'nombre' => 'Promo', 'segmento' => 'todos', 'texto' => 'Hola', 'estado' => 'borrador',
        ]);

        $resultado = Campanas::enviar($campana);

        $this->assertSame('enviada', $resultado->estado);
        $this->assertSame(1, $resultado->enviados);
        $this->assertSame(1, $resultado->fallidos);
    }
}
