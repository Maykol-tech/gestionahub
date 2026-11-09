<?php

namespace Tests\Feature;

use App\Models\ComprobanteElectronico;
use App\Models\SunatConfiguracion;
use App\Models\Tenant;
use App\Support\Nubefact;
use App\Support\NubefactNotas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NubefactNotasTest extends TestCase
{
    use RefreshDatabase;

    private function config(): SunatConfiguracion
    {
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        $config = new SunatConfiguracion([
            'ruta' => 'https://api.nubefact.test/api/v1/demo', 'serie_factura' => 'F001', 'serie_boleta' => 'B001',
            'ambiente' => 'demo', 'activo' => true,
        ]);
        $config->tenant_id = $tenant->id;
        $config->guardarToken('token-prueba');
        $config->save();

        return $config;
    }

    private function boletaAceptada(SunatConfiguracion $config): ComprobanteElectronico
    {
        Http::fake(['*' => Http::response(['aceptada_por_sunat' => true, 'sunat_description' => 'ok'], 200)]);

        return Nubefact::emitir($config, '03', ['nombre' => 'Ana'], [
            ['descripcion' => 'Pastel', 'cantidad' => 2, 'precio_unitario' => 50],
            ['descripcion' => 'Café', 'cantidad' => 1, 'precio_unitario' => 10],
        ]);
    }

    public function test_anulacion_marca_el_comprobante_como_anulado(): void
    {
        $config = $this->config();
        $boleta = $this->boletaAceptada($config);

        Http::fake(['*' => Http::response(['sunat_description' => 'Baja registrada'], 200)]);

        $resultado = NubefactNotas::anular($boleta, $config, 'Error de digitación');

        $this->assertSame('anulado', $resultado->estado);
        $this->assertFalse($resultado->aceptado);
        Http::assertSent(fn ($r) => $r['operacion'] === 'generar_anulacion' && $r['numero'] === $boleta->numero);
    }

    public function test_nota_credito_total_usa_los_items_del_comprobante(): void
    {
        $config = $this->config();
        $boleta = $this->boletaAceptada($config);

        Http::fake(['*' => Http::response(['aceptada_por_sunat' => true], 200)]);

        $nota = NubefactNotas::notaCredito($boleta, $config, '06', 'Devolución del cliente');

        $this->assertSame('07', $nota->tipo);
        $this->assertSame('aceptado', $nota->estado);
        $this->assertSame((float) $boleta->total, (float) $nota->total);
        $this->assertSame('B001', $nota->serie);
        $this->assertSame(1, $nota->numero);

        Http::assertSent(fn ($r) => $r['tipo_de_comprobante'] === 3
            && $r['codigo_nota_credito'] === '06'
            && $r['documento_que_se_modifica_numero'] === $boleta->numero);
    }

    public function test_nota_parcial_no_puede_superar_el_total(): void
    {
        $config = $this->config();
        $boleta = $this->boletaAceptada($config);

        $this->expectException(ValidationException::class);
        NubefactNotas::notaCredito($boleta, $config, '07', 'Devolución', [
            ['descripcion' => 'Pastel', 'cantidad' => 10, 'precio_unitario' => 50],
        ]);
    }

    public function test_no_se_anula_un_comprobante_pendiente(): void
    {
        $config = $this->config();
        $pendiente = ComprobanteElectronico::withoutGlobalScopes()->create([
            'tenant_id' => $config->tenant_id, 'tipo' => '03', 'serie' => 'B001', 'numero' => 99,
            'cliente_nombre' => 'Ana', 'total' => 10, 'estado' => 'pendiente',
        ]);

        $this->expectException(ValidationException::class);
        NubefactNotas::anular($pendiente, $config, 'Prueba');
    }
}
