<?php

namespace Tests\Feature;

use App\Models\ComprobanteElectronico;
use App\Models\SunatConfiguracion;
use App\Models\Tenant;
use App\Support\Nubefact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NubefactTest extends TestCase
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

    public function test_los_totales_cuadran_con_igv_incluido(): void
    {
        $calculo = Nubefact::calcular([
            ['descripcion' => 'Pastel', 'cantidad' => 1, 'precio_unitario' => 118],
            ['descripcion' => 'Café', 'cantidad' => 2, 'precio_unitario' => 11.80],
        ]);

        $this->assertSame(141.6, $calculo['total']);
        $this->assertSame(120.0, $calculo['gravada']);
        $this->assertSame(21.6, $calculo['igv']);
        $this->assertCount(2, $calculo['items']);
    }

    public function test_la_factura_exige_ruc_y_la_boleta_dni_desde_700(): void
    {
        $this->expectException(ValidationException::class);
        Nubefact::validarCliente('01', '12345678', 100);
    }

    public function test_boleta_grande_sin_dni_se_rechaza(): void
    {
        $this->expectException(ValidationException::class);
        Nubefact::validarCliente('03', null, 750);
    }

    public function test_emite_boleta_aceptada_y_numera_en_secuencia(): void
    {
        Http::fake([
            '*' => Http::response([
                'aceptada_por_sunat' => true,
                'sunat_description' => 'La boleta ha sido aceptada',
                'enlace_del_pdf' => 'https://nubefact.test/pdf/1',
                'codigo_hash' => 'abc',
            ], 200),
        ]);

        $config = $this->config();
        $items = [['descripcion' => 'Pastel', 'cantidad' => 1, 'precio_unitario' => 50]];

        $primera = Nubefact::emitir($config, '03', ['nombre' => 'Ana'], $items);
        $segunda = Nubefact::emitir($config, '03', ['nombre' => 'Luis'], $items);

        $this->assertSame('aceptado', $primera->estado);
        $this->assertTrue($primera->aceptado);
        $this->assertSame(1, $primera->numero);
        $this->assertSame(2, $segunda->numero);
        $this->assertSame('B001-00000002', $segunda->numero_completo);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Token token="token-prueba"')
                && $request['operacion'] === 'generar_comprobante'
                && $request['tipo_de_comprobante'] === 2;
        });
    }

    public function test_si_nubefact_no_responde_queda_en_error_y_conserva_el_numero(): void
    {
        Http::fake(['*' => Http::response(['errors' => 'Token inválido'], 401)]);

        $config = $this->config();
        $comprobante = Nubefact::emitir($config, '01', ['documento' => '20123456789', 'nombre' => 'Empresa SAC'], [
            ['descripcion' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 236],
        ]);

        $this->assertSame('error', $comprobante->estado);
        $this->assertFalse($comprobante->aceptado);
        $this->assertSame('F001', $comprobante->serie);
        $this->assertSame(1, ComprobanteElectronico::withoutGlobalScopes()->count());
    }
}
