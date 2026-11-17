<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportarTablaRequest;
use App\Models\ComprobanteElectronico;
use App\Models\CrmCliente;
use App\Services\ExportadorExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exportaciones a Excel (.xlsx) con Laravel-Excel.
 *  - tabla(): exporta exactamente lo que el usuario ve filtrado en pantalla (productos, pedidos, caja, cobros...).
 *  - clientes() y comprobantes(): exportaciones completas armadas en el servidor.
 */
class ExportacionController extends Controller
{
    public function __construct(private ExportadorExcel $excel) {}

    public function tabla(ExportarTablaRequest $request): Response
    {
        $datos = $request->validated();

        return $this->seguro(fn () => $this->excel->descargar(
            $datos['titulo'], $datos['columnas'], $datos['filas'], (bool) ($datos['totales'] ?? true)
        ));
    }

    /** Clientes con sus puntos de fidelizacion (reemplaza al CSV anterior). */
    public function clientes(): Response
    {
        return $this->seguro(function () {
            $filas = [];
            CrmCliente::query()->orderBy('nombre')->select(['id', 'nombre', 'telefono', 'puntos_saldo', 'puntos_historicos'])
                ->chunk(1000, function ($clientes) use (&$filas) {
                    foreach ($clientes as $c) {
                        $filas[] = [$c->nombre, $c->telefono, (int) $c->puntos_saldo, (int) $c->puntos_historicos];
                    }
                });

            return $this->excel->descargar('Clientes y puntos', [
                ['titulo' => 'Nombre'], ['titulo' => 'Teléfono'],
                ['titulo' => 'Puntos disponibles', 'tipo' => 'numero'], ['titulo' => 'Puntos históricos', 'tipo' => 'numero'],
            ], $filas, false);
        });
    }

    /** Comprobantes electronicos con los mismos filtros de la pantalla. */
    public function comprobantes(Request $request): Response
    {
        $datos = $request->validate([
            'tipo' => ['nullable', 'in:' . implode(',', array_keys(ComprobanteElectronico::TIPOS))],
            'estado' => ['nullable', 'in:' . implode(',', ComprobanteElectronico::ESTADOS)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->seguro(function () use ($datos) {
            $filas = ComprobanteElectronico::query()->with('referencia:id,serie,numero')
                ->when($datos['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
                ->when($datos['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
                ->when($datos['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('cliente_nombre', 'like', "%{$t}%")->orWhere('cliente_documento', 'like', "%{$t}%")))
                ->orderByDesc('id')->limit(ExportarTablaRequest::MAX_FILAS)->get()
                ->map(fn (ComprobanteElectronico $c) => [
                    $c->numero_completo, $c->nombre_tipo, ($c->emitido_at ?? $c->created_at)?->timezone('America/Lima')->format('d/m/Y H:i'),
                    $c->cliente_nombre, $c->cliente_documento, (float) $c->subtotal, (float) $c->igv, (float) $c->total,
                    ucfirst($c->estado), $c->simulado ? 'Sí' : 'No', $c->referencia?->numero_completo,
                ])->all();

            return $this->excel->descargar('Comprobantes electrónicos', [
                ['titulo' => 'Número'], ['titulo' => 'Tipo'], ['titulo' => 'Fecha', 'tipo' => 'fecha'],
                ['titulo' => 'Cliente'], ['titulo' => 'DNI/RUC'],
                ['titulo' => 'Op. gravada', 'tipo' => 'moneda'], ['titulo' => 'IGV', 'tipo' => 'moneda'], ['titulo' => 'Total', 'tipo' => 'moneda'],
                ['titulo' => 'Estado'], ['titulo' => 'Simulado'], ['titulo' => 'Modifica a'],
            ], $filas);
        });
    }

    /** Si algo falla al generar el archivo, el usuario ve un mensaje claro y el detalle queda en el log. */
    private function seguro(callable $generar): Response
    {
        try {
            return $generar();
        } catch (\Throwable $e) {
            Log::error('Error al exportar a Excel', ['error' => $e->getMessage(), 'archivo' => $e->getFile() . ':' . $e->getLine()]);

            return request()->expectsJson()
                ? response()->json(['message' => 'No se pudo generar el Excel. Intenta de nuevo en unos segundos.'], 500)
                : back()->with('error', 'No se pudo generar el Excel. Intenta de nuevo en unos segundos.');
        }
    }
}
