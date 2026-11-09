<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Pedido;
use App\Models\Recompensa;
use App\Models\Tenant;
use App\Support\Fidelizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FidelizacionTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): CrmCliente
    {
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        return CrmCliente::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'nombre' => 'Cliente Demo', 'telefono' => '999000111', 'activo' => true,
        ]);
    }

    private function pedido(CrmCliente $cliente, float $total): Pedido
    {
        return Pedido::withoutGlobalScopes()->create([
            'tenant_id' => $cliente->tenant_id, 'cliente_id' => $cliente->id, 'numero' => 'P-' . random_int(1000, 9999),
            'tipo' => 'recojo', 'metodo_pago' => 'efectivo', 'estado' => 'pendiente', 'pagado' => false,
            'subtotal' => $total, 'total' => $total,
        ]);
    }

    public function test_pagar_un_pedido_da_un_punto_por_sol(): void
    {
        $cliente = $this->cliente();
        $pedido = $this->pedido($cliente, 45.90);

        $pedido->update(['pagado' => true]);

        $cliente->refresh();
        $this->assertSame(45, $cliente->puntos_saldo);
        $this->assertSame(45, $cliente->puntos_historicos);
    }

    public function test_pagar_dos_veces_no_duplica_los_puntos(): void
    {
        $cliente = $this->cliente();
        $pedido = $this->pedido($cliente, 30);

        $pedido->update(['pagado' => true]);
        $pedido->update(['estado' => 'entregado']);

        $this->assertSame(30, $cliente->refresh()->puntos_saldo);
    }

    public function test_cancelar_revierte_los_puntos(): void
    {
        $cliente = $this->cliente();
        $pedido = $this->pedido($cliente, 60);
        $pedido->update(['pagado' => true]);

        $pedido->update(['estado' => 'cancelado']);

        $this->assertSame(0, $cliente->refresh()->puntos_saldo);
    }

    public function test_canje_descuenta_puntos_y_respeta_el_saldo(): void
    {
        $cliente = $this->cliente();
        $this->pedido($cliente, 200)->update(['pagado' => true]);
        $recompensa = Recompensa::withoutGlobalScopes()->create([
            'tenant_id' => $cliente->tenant_id, 'nombre' => 'Chicha gratis', 'puntos_requeridos' => 150, 'activo' => true,
        ]);

        Fidelizacion::canjear($cliente, $recompensa);
        $this->assertSame(50, $cliente->refresh()->puntos_saldo);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Fidelizacion::canjear($cliente, $recompensa);
    }

    public function test_niveles_segun_puntos_historicos(): void
    {
        $this->assertSame('bronce', Fidelizacion::nivelDe(0));
        $this->assertSame('plata', Fidelizacion::nivelDe(500));
        $this->assertSame('oro', Fidelizacion::nivelDe(1500));
        $this->assertSame(1.5, Fidelizacion::multiplicador(2000));
    }

    public function test_el_historial_queda_registrado(): void
    {
        $cliente = $this->cliente();
        $this->pedido($cliente, 25)->update(['pagado' => true]);

        $this->assertSame(1, FidelizacionMovimiento::withoutGlobalScopes()->where('cliente_id', $cliente->id)->where('tipo', 'ganado')->count());
    }
}
