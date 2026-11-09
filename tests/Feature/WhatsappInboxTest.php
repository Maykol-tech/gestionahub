<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsappConexion;
use App\Models\WhatsappSesion;
use App\Support\WhatsAppNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_mensaje_entrante_suma_a_la_sesion_del_cliente(): void
    {
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        $conexion = new WhatsappConexion(['phone_id' => '123', 'numero_visible' => '999', 'activo' => true]);
        $conexion->tenant_id = $tenant->id;
        $conexion->guardarToken('token');
        $conexion->save();

        WhatsAppNegocio::procesarEntrante($conexion, '51999000111', 'hola');
        WhatsAppNegocio::procesarEntrante($conexion, '51999000111', 'quiero pedir');

        $sesion = WhatsappSesion::withoutGlobalScopes()->where('telefono', '51999000111')->firstOrFail();

        $this->assertSame(2, $sesion->mensajes);
        $this->assertTrue($sesion->activa);
    }
}
