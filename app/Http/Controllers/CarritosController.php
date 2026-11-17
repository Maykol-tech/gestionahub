<?php

namespace App\Http\Controllers;

use App\Models\CheckoutAbandonado;
use App\Models\Tenant;
use App\Support\AjustesNegocio;
use App\Support\CarritosAbandonados;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** GEST-85: carritos abandonados de la tienda, recordatorio por WhatsApp y reporte de recuperados. */
class CarritosController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();
        $tenantId = (int) auth()->user()->tenant_id;
        $negocio = Tenant::find($tenantId);
        $cfg = CarritosAbandonados::config($tenantId);
        $filtro = in_array($request->query('estado'), ['pendientes', 'recordados', 'recuperados'], true) ? $request->query('estado') : 'todos';

        $lista = CheckoutAbandonado::query()->orderByDesc('id')
            ->when($filtro === 'pendientes', fn ($q) => $q->where('recuperado', false)->whereNull('recordado_at'))
            ->when($filtro === 'recordados', fn ($q) => $q->where('recuperado', false)->whereNotNull('recordado_at'))
            ->when($filtro === 'recuperados', fn ($q) => $q->where('recuperado', true))
            ->limit(100)->get()
            ->map(function (CheckoutAbandonado $c) use ($negocio, $cfg) {
                if (! $c->token) {
                    $c->forceFill(['token' => bin2hex(random_bytes(16))])->saveQuietly();
                }
                $tel = preg_replace('/\D/', '', (string) $c->telefono);

                return [
                    'id' => $c->id, 'nombre' => $c->nombre ?: 'Sin nombre', 'telefono' => $c->telefono,
                    'productos' => collect((array) $c->carrito)->sum(fn ($l) => (int) ($l['cantidad'] ?? 1)),
                    'detalle' => collect((array) $c->carrito)->map(fn ($l) => ($l['cantidad'] ?? 1) . '× ' . ($l['nombre'] ?? 'Producto'))->implode(', '),
                    'total' => (float) $c->total,
                    'fecha' => $c->updated_at?->timezone('America/Lima')->format('d/m H:i'),
                    'estado' => $c->recuperado ? 'recuperado' : ($c->recordado_at ? 'recordado' : 'pendiente'),
                    'abierto' => (bool) $c->abierto_at,
                    'wa' => $tel && $negocio?->slug ? 'https://wa.me/' . (strlen($tel) === 9 ? '51' . $tel : $tel) . '?text=' . rawurlencode(CarritosAbandonados::mensaje($negocio, $c, $cfg)) : null,
                ];
            });

        return view('admin.modulos.carritos', [
            'titulo' => 'Carritos abandonados',
            'modulo' => 'carritos',
            'cfg' => $cfg,
            'resumen' => CarritosAbandonados::resumen($tenantId),
            'lista' => $lista,
            'filtro' => $filtro,
            'tieneTienda' => (bool) $negocio?->slug,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'carrito_activo' => ['sometimes', 'boolean'],
            'carrito_minutos' => ['required', 'integer', 'between:10,1440'],
            'carrito_cupon' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]*$/'],
            'carrito_mensaje' => ['required', 'string', 'max:500'],
        ], [
            'carrito_minutos.between' => 'El tiempo de espera va de 10 a 1440 minutos.',
            'carrito_cupon.regex' => 'El cupón solo puede tener letras, números, guion y guion bajo.',
            'carrito_mensaje.required' => 'Escribe el mensaje del recordatorio.',
        ]);

        AjustesNegocio::guardar((int) auth()->user()->tenant_id, [
            'carrito_activo' => $request->boolean('carrito_activo'),
            'carrito_minutos' => (int) $datos['carrito_minutos'],
            'carrito_cupon' => strtoupper((string) ($datos['carrito_cupon'] ?? '')),
            'carrito_mensaje' => $datos['carrito_mensaje'],
        ]);

        return back()->with('ok', 'Recordatorio guardado.');
    }

    /** Marca que se envió el recordatorio a mano (desde el botón de WhatsApp). */
    public function marcarRecordado(CheckoutAbandonado $carrito): RedirectResponse
    {
        if (! $carrito->recordado_at) {
            $carrito->forceFill(['recordado_at' => now(), 'recordatorio_ok' => true])->save();
        }

        return back()->with('ok', 'Marcado como recordado.');
    }
}
