<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Tenant;
use App\Support\Comandas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MesasComandasTest extends TestCase
{
    use RefreshDatabase;

    private function mesa(): Mesa
    {
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        $mesa = new Mesa(['nombre' => 'Mesa 1', 'capacidad' => 4]);
        $mesa->tenant_id = $tenant->id;
        $mesa->save();

        return $mesa;
    }

    private function producto(Mesa $mesa, int $stock = 5): Producto
    {
        return Producto::withoutGlobalScopes()->create([
            'tenant_id' => $mesa->tenant_id, 'nombre' => 'Lomo saltado', 'precio' => 30, 'stock' => $stock, 'activo' => true,
        ]);
    }

    public function test_la_mesa_recibe_token_para_su_qr(): void
    {
        $mesa = $this->mesa();

        $this->assertNotEmpty($mesa->token);
        $this->assertStringEndsWith('mesa/' . $mesa->token, $mesa->urlPublica());
    }

    public function test_la_comanda_descuenta_stock_y_ocupa_la_mesa(): void
    {
        $mesa = $this->mesa();
        $producto = $this->producto($mesa, 5);

        $comanda = Comandas::crear($mesa, [['producto_id' => $producto->id, 'cantidad' => 2]], 'sin cebolla');

        $this->assertSame('pendiente', $comanda->estado);
        $this->assertSame(60.0, (float) $comanda->total);
        $this->assertSame(3, (int) $producto->fresh()->stock);
        $this->assertSame('ocupada', $mesa->fresh()->estado);
    }

    public function test_sin_stock_no_se_crea_la_comanda(): void
    {
        $mesa = $this->mesa();
        $producto = $this->producto($mesa, 1);

        $this->expectException(ValidationException::class);
        Comandas::crear($mesa, [['producto_id' => $producto->id, 'cantidad' => 2]]);
    }

    public function test_cancelar_devuelve_el_stock(): void
    {
        $mesa = $this->mesa();
        $producto = $this->producto($mesa, 5);
        $comanda = Comandas::crear($mesa, [['producto_id' => $producto->id, 'cantidad' => 2]]);

        Comandas::cambiarEstado($comanda, 'cancelada');

        $this->assertSame(5, (int) $producto->fresh()->stock);
    }

    public function test_no_se_puede_saltar_etapas_de_cocina(): void
    {
        $mesa = $this->mesa();
        $producto = $this->producto($mesa, 5);
        $comanda = Comandas::crear($mesa, [['producto_id' => $producto->id, 'cantidad' => 1]]);

        $this->expectException(ValidationException::class);
        Comandas::cambiarEstado($comanda, 'entregada');
    }

    public function test_el_flujo_completo_llega_a_cobrada(): void
    {
        $mesa = $this->mesa();
        $producto = $this->producto($mesa, 5);
        $comanda = Comandas::crear($mesa, [['producto_id' => $producto->id, 'cantidad' => 1]]);

        foreach (['en_preparacion', 'lista', 'entregada', 'cobrada'] as $estado) {
            $comanda = Comandas::cambiarEstado($comanda, $estado);
        }

        $this->assertSame('cobrada', $comanda->estado);
        $this->assertSame(0, Comanda::withoutGlobalScopes()->whereIn('estado', Comanda::ABIERTAS)->count());
    }
}
