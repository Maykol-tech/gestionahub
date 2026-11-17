<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronico;
use App\Models\Pedido;
use App\Models\SunatConfiguracion;
use App\Models\Tenant;
use App\Models\VentaPos;
use App\Support\Nubefact;
use App\Support\NubefactNotas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Modulo A - Facturacion electronica.
 * Boletas, facturas y notas de credito (SUNAT vía Nubefact o modo simulado):
 * configuracion, listado con filtros, emision desde pedidos y ventas POS, y representacion impresa.
 */
class ComprobanteController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $config = SunatConfiguracion::query()->first();
        $filtros = [
            'tipo' => in_array($request->query('tipo'), array_keys(ComprobanteElectronico::TIPOS), true) ? $request->query('tipo') : null,
            'estado' => in_array($request->query('estado'), ComprobanteElectronico::ESTADOS, true) ? $request->query('estado') : null,
            'q' => trim((string) $request->query('q', '')),
        ];

        $comprobantes = ComprobanteElectronico::query()->with('referencia')
            ->when($filtros['tipo'], fn ($q, $t) => $q->where('tipo', $t))
            ->when($filtros['estado'], fn ($q, $e) => $q->where('estado', $e))
            ->when($filtros['q'] !== '', function ($q) use ($filtros) {
                $texto = $filtros['q'];
                $q->where(function ($w) use ($texto) {
                    $w->where('cliente_nombre', 'like', "%{$texto}%")
                        ->orWhere('cliente_documento', 'like', "%{$texto}%");
                    if (preg_match('/^([BF]\w{3})-?0*(\d+)$/i', $texto, $m)) {
                        $w->orWhere(fn ($x) => $x->where('serie', strtoupper($m[1]))->where('numero', (int) $m[2]));
                    }
                });
            })
            ->orderByDesc('id')->paginate(15)->withQueryString();

        $mes = ComprobanteElectronico::query()->where('created_at', '>=', now()->startOfMonth());

        return view('admin.modulos.comprobantes', [
            'titulo' => 'Comprobantes electrónicos',
            'modulo' => 'comprobantes',
            'config' => $config,
            'ambientes' => SunatConfiguracion::AMBIENTES,
            'comprobantes' => $comprobantes,
            'filtros' => $filtros,
            'tipos' => ComprobanteElectronico::TIPOS,
            'estados' => ComprobanteElectronico::ESTADOS,
            'motivosNota' => NubefactNotas::CODIGOS,
            'stats' => [
                'emitidos' => (clone $mes)->whereIn('tipo', ['01', '03'])->where('estado', 'aceptado')->count(),
                'facturado' => round((float) (clone $mes)->whereIn('tipo', ['01', '03'])->where('estado', 'aceptado')->sum('total'), 2),
                'notas' => (clone $mes)->where('tipo', '07')->count(),
                'con_error' => (clone $mes)->whereIn('estado', ['error', 'rechazado'])->count(),
            ],
            'pedidosPendientes' => Pedido::query()->where('pagado', true)
                ->whereDoesntHave('comprobantes')->with('cliente')->orderByDesc('id')->limit(6)->get(),
            'ventasPendientes' => VentaPos::query()->vigentes()->whereDate('created_at', '>=', now()->subDays(7))
                ->whereDoesntHave('comprobantes')->orderByDesc('id')->limit(6)->get(),
        ]);
    }

    public function guardarConfig(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'ambiente' => ['required', Rule::in(array_keys(SunatConfiguracion::AMBIENTES))],
            'ruta' => ['nullable', 'required_unless:ambiente,simulado', 'url', 'max:255'],
            'token' => ['nullable', 'string', 'max:255'],
            'serie_factura' => ['required', 'regex:/^F[A-Z0-9]{3}$/i'],
            'serie_boleta' => ['required', 'regex:/^B[A-Z0-9]{3}$/i'],
            'activo' => ['sometimes', 'boolean'],
        ], [
            'ruta.required_unless' => 'Para conectar con Nubefact necesitas la URL de tu API.',
            'serie_factura.regex' => 'La serie de factura empieza con F y tiene 4 caracteres (ej. F001).',
            'serie_boleta.regex' => 'La serie de boleta empieza con B y tiene 4 caracteres (ej. B001).',
        ]);

        $config = SunatConfiguracion::query()->firstOrNew([]);

        if ($datos['ambiente'] !== 'simulado') {
            $tokenGuardado = $config->exists && $config->getRawOriginal('token') !== null;
            if (empty($datos['token']) && ! $tokenGuardado) {
                return back()->withErrors(['token' => 'Para conectar con Nubefact necesitas el token.'])->withInput();
            }
        }
        if ($datos['ambiente'] === 'produccion' && ! str_starts_with((string) $datos['ruta'], 'https://')) {
            return back()->withErrors(['ruta' => 'Para producción la URL de Nubefact debe empezar con https://.'])->withInput();
        }

        $config->fill([
            'ruta' => $datos['ruta'] ?? null,
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

        return back()->with('ok', 'Configuración de facturación guardada.');
    }

    public function emitir(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $this->validarCliente($request);

        $pedido->loadMissing('items', 'cliente');
        $items = $pedido->items->map(fn ($it) => [
            'descripcion' => (string) ($it->nombre ?? 'Producto'),
            'cantidad' => (float) ($it->cantidad ?? 1),
            'precio_unitario' => (float) ($it->precio_unitario ?? 0),
        ])->values()->all();

        if (empty($items)) {
            return back()->withErrors(['config' => 'El pedido no tiene productos.']);
        }

        $comprobante = Nubefact::emitir(SunatConfiguracion::delNegocio(), $datos['tipo'], [
            'documento' => $datos['documento'] ?? null,
            'nombre' => ($datos['nombre'] ?? null) ?: ($pedido->cliente->nombre ?? null),
            'direccion' => $datos['direccion'] ?? $pedido->direccion_entrega,
        ], $items, $pedido->id);

        return $this->resultado($comprobante);
    }

    /** Emite boleta o factura para una venta POS que se cobro sin comprobante (o cuyo envio fallo). */
    public function emitirVenta(Request $request, int $venta): RedirectResponse
    {
        $datos = $this->validarCliente($request);
        $venta = VentaPos::query()->findOrFail($venta);

        if ($venta->anulada) {
            return back()->withErrors(['config' => 'La venta está anulada.']);
        }
        if ($venta->comprobantes()->whereIn('tipo', ['01', '03'])->where('estado', 'aceptado')->exists()) {
            return back()->withErrors(['config' => 'La venta ya tiene un comprobante aceptado.']);
        }

        $items = collect((array) $venta->items)->map(fn ($l) => [
            'codigo' => (string) ($l['codigo'] ?? 'P' . ($l['producto_id'] ?? '')),
            'descripcion' => (string) $l['nombre'],
            'cantidad' => (float) $l['cantidad'],
            'precio_unitario' => (float) $l['precio'],
        ])->all();

        $comprobante = Nubefact::emitir(SunatConfiguracion::delNegocio(), $datos['tipo'], [
            'documento' => $datos['documento'] ?? $venta->cliente_documento,
            'nombre' => ($datos['nombre'] ?? null) ?: ($venta->cliente_nombre ?: 'CLIENTES VARIOS'),
            'direccion' => $datos['direccion'] ?? null,
        ], $items, null, $venta->id);

        $venta->update(['tipo_comprobante' => $datos['tipo'] === '01' ? 'factura' : 'boleta']);

        return $this->resultado($comprobante);
    }

    public function anular(Request $request, ComprobanteElectronico $comprobante): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:3', 'max:200']], [
            'motivo.required' => 'Indica el motivo de la anulación.',
        ]);

        $resultado = NubefactNotas::anular($comprobante, SunatConfiguracion::delNegocio(), $datos['motivo']);

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
            'codigo' => ['required', Rule::in(array_keys(NubefactNotas::CODIGOS))],
            'motivo' => ['required', 'string', 'min:3', 'max:200'],
            'monto' => ['nullable', 'numeric', 'min:0.01', 'required_if:codigo,09'],
        ], [
            'motivo.required' => 'Indica el motivo de la nota de crédito.',
            'monto.required_if' => 'Indica el monto a descontar.',
        ]);

        // Disminucion en el valor: una sola linea por el monto indicado
        $items = null;
        if (in_array($datos['codigo'], ['07', '09'], true)) {
            if (empty($datos['monto'])) {
                throw ValidationException::withMessages(['monto' => 'Indica el monto de la nota de crédito.']);
            }
            $items = [[
                'codigo' => 'NC', 'descripcion' => NubefactNotas::CODIGOS[$datos['codigo']] . ': ' . $datos['motivo'],
                'cantidad' => 1, 'precio_unitario' => (float) $datos['monto'],
            ]];
        }

        $nota = NubefactNotas::notaCredito($comprobante, SunatConfiguracion::delNegocio(), $datos['codigo'], $datos['motivo'], $items);

        return back()->with(
            $nota->aceptado ? 'ok' : 'error',
            $nota->aceptado
                ? "Nota de crédito {$nota->numero_completo} aceptada" . ($nota->simulado ? ' (simulada).' : ' por SUNAT.')
                : "Nota de crédito {$nota->numero_completo}: " . ($nota->mensaje ?: $nota->estado)
        );
    }

    /** Representacion impresa del comprobante (A4 o ticket con ?formato=ticket). */
    public function ver(Request $request, ComprobanteElectronico $comprobante)
    {
        $comprobante->loadMissing('referencia');

        return view('admin.comprobantes.impresion', [
            'c' => $comprobante,
            'negocio' => Tenant::find($comprobante->tenant_id),
            'items' => $comprobante->payload['items'] ?? [],
            'ticket' => $request->query('formato') === 'ticket',
        ]);
    }

    // ----------------------------------------------------------------------------------------

    private function validarCliente(Request $request): array
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:01,03'],
            'documento' => ['nullable', 'string', 'max:20'],
            'nombre' => ['nullable', 'string', 'max:150', 'required_if:tipo,01'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required_if' => 'Para factura escribe la razón social.',
        ]);

        if ($datos['tipo'] === '01' && strlen(preg_replace('/\D/', '', (string) ($datos['documento'] ?? ''))) !== 11) {
            throw ValidationException::withMessages(['documento' => 'Para factura necesitas el RUC (11 dígitos).']);
        }

        return $datos;
    }

    private function resultado(ComprobanteElectronico $c): RedirectResponse
    {
        return back()->with(
            $c->aceptado ? 'ok' : 'error',
            $c->aceptado
                ? "{$c->nombre_tipo} {$c->numero_completo} aceptada" . ($c->simulado ? ' (modo simulado).' : ' por SUNAT.')
                : "{$c->nombre_tipo} {$c->numero_completo}: " . ($c->mensaje ?: $c->estado)
        );
    }
}
