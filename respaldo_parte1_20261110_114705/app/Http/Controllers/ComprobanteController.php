<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronico;
use App\Models\Pedido;
use App\Models\SunatConfiguracion;
use App\Support\Nubefact;
use App\Support\NubefactNotas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Boletas y facturas electronicas (SUNAT vía Nubefact): configuracion, listado y emision. */
class ComprobanteController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $config = SunatConfiguracion::query()->first();

        return view('admin.modulos.comprobantes', [
            'titulo' => 'Comprobantes electrónicos',
            'modulo' => 'comprobantes',
            'config' => $config,
            'comprobantes' => ComprobanteElectronico::query()->orderByDesc('id')->limit(100)->get(),
            'pedidosPendientes' => Pedido::query()->where('pagado', true)
                ->whereDoesntHave('comprobantes')->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function guardarConfig(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'ruta' => ['required', 'url', 'max:255'],
            'token' => ['nullable', 'string', 'max:255'],
            'serie_factura' => ['required', 'regex:/^F[A-Z0-9]{3}$/'],
            'serie_boleta' => ['required', 'regex:/^B[A-Z0-9]{3}$/'],
            'ambiente' => ['required', 'in:demo,produccion'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $config = SunatConfiguracion::query()->firstOrNew([]);

        // Produccion exige token guardado y URL https; sin eso los comprobantes serian rechazados o irian a demo.
        if ($datos['ambiente'] === 'produccion') {
            $tokenGuardado = $config->exists && $config->token !== null;
            if (empty($datos['token']) && ! $tokenGuardado) {
                return back()->withErrors(['token' => 'Para producción necesitas el token de Nubefact.'])->withInput();
            }
            if (! str_starts_with($datos['ruta'], 'https://')) {
                return back()->withErrors(['ruta' => 'Para producción la URL de Nubefact debe empezar con https://.'])->withInput();
            }
        }

        $config->fill([
            'ruta' => $datos['ruta'],
            'serie_factura' => strtoupper($datos['serie_factura']),
            'serie_boleta' => strtoupper($datos['serie_boleta']),
            'ambiente' => $datos['ambiente'],
            'activo' => (bool) ($datos['activo'] ?? false),
        ]);

        // Si el campo llega vacio se conserva el token que ya estaba guardado
        if (! empty($datos['token'])) {
            $config->guardarToken($datos['token']);
        }

        if (! $config->exists) {
            $config->tenant_id = auth()->user()->tenant_id;
        }
        $config->save();

        return back()->with('ok', 'Configuración de Nubefact guardada.');
    }

    public function emitir(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:01,03'],
            'documento' => ['nullable', 'string', 'max:20'],
            'nombre' => ['nullable', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $config = SunatConfiguracion::query()->first();
        if (! $config) {
            return back()->withErrors(['config' => 'Configura Nubefact primero.']);
        }

        $pedido->loadMissing('items');
        $items = $pedido->items->map(fn ($it) => [
            'descripcion' => (string) ($it->nombre ?? 'Producto'),
            'cantidad' => (float) ($it->cantidad ?? 1),
            'precio_unitario' => (float) ($it->precio_unitario ?? 0),
        ])->values()->all();

        if (empty($items)) {
            return back()->withErrors(['config' => 'El pedido no tiene productos.']);
        }

        $comprobante = Nubefact::emitir($config, $datos['tipo'], [
            'documento' => $datos['documento'] ?? null,
            'nombre' => $datos['nombre'] ?: ($pedido->cliente->nombre ?? null),
            'direccion' => $datos['direccion'] ?? $pedido->direccion_entrega,
        ], $items, $pedido->id);

        return back()->with(
            $comprobante->aceptado ? 'ok' : 'error',
            $comprobante->aceptado
                ? "Comprobante {$comprobante->numero_completo} aceptado por SUNAT."
                : "Comprobante {$comprobante->numero_completo}: " . ($comprobante->mensaje ?: $comprobante->estado)
        );
    }

    public function anular(Request $request, ComprobanteElectronico $comprobante): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:200']]);

        $config = SunatConfiguracion::query()->first();
        if (! $config) {
            return back()->withErrors(['config' => 'Configura Nubefact primero.']);
        }

        $resultado = NubefactNotas::anular($comprobante, $config, $datos['motivo']);

        return back()->with(
            $resultado->estado === 'anulado' ? 'ok' : 'error',
            $resultado->estado === 'anulado'
                ? "Comprobante {$resultado->numero_completo} anulado."
                : "No se pudo anular {$resultado->numero_completo}: " . ($resultado->mensaje ?: $resultado->estado)
        );
    }

    public function notaCredito(Request $request, ComprobanteElectronico $comprobante): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'in:' . implode(',', array_keys(NubefactNotas::CODIGOS))],
            'motivo' => ['required', 'string', 'max:200'],
        ]);

        $config = SunatConfiguracion::query()->first();
        if (! $config) {
            return back()->withErrors(['config' => 'Configura Nubefact primero.']);
        }

        $nota = NubefactNotas::notaCredito($comprobante, $config, $datos['codigo'], $datos['motivo']);

        return back()->with(
            $nota->aceptado ? 'ok' : 'error',
            $nota->aceptado
                ? "Nota de crédito {$nota->numero_completo} aceptada por SUNAT."
                : "Nota de crédito {$nota->numero_completo}: " . ($nota->mensaje ?: $nota->estado)
        );
    }
}
