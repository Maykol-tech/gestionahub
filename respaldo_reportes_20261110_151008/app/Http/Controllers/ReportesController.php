<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteExportarRequest;
use App\Services\ExportadorExcel;
use App\Services\ReportesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportes GEST-40 (ventas por periodo y modulo) y GEST-41 (delivery: tiempos y repartidores).
 * La pantalla /admin/reportes la sirve ModulosController::datosReportes() con pantalla();
 * aqui viven la logica compartida y la descarga a Excel.
 */
class ReportesController extends Controller
{
    public const VISTAS = ['ventas', 'repartidores'];

    public function __construct(
        private ReportesService $reportes,
        private ExportadorExcel $excel,
    ) {}

    /** Datos de la vista admin/modulos/reportes. */
    public function pantalla(Request $request, int $tenantId): array
    {
        $rango = ReportesService::rangoValido($request->query('rango', 7));
        $vista = in_array($request->query('vista'), self::VISTAS, true) ? $request->query('vista') : 'ventas';
        [$desde, $hasta] = $this->reportes->periodo($rango);

        return array_merge($this->reportes->ventas($tenantId, $rango), [
            'rango' => $rango,
            'vista' => $vista,
            'desdeTexto' => $desde->format('d/m/Y'),
            'hastaTexto' => $hasta->format('d/m/Y'),
            'delivery' => $this->reportes->repartidores($tenantId, $rango),
            'hayPedidos' => true,
        ]);
    }

    /** GET /admin/reportes/exportar?rango=7|30|90&hoja=todo|ventas|repartidores */
    public function exportar(ReporteExportarRequest $request): Response
    {
        $tenantId = (int) $request->user()->tenant_id;
        $rango = $request->rango();

        try {
            return match ($request->hoja()) {
                'ventas' => $this->descargarHoja($this->reportes->hojaVentas($tenantId, $rango), "Ventas por canal {$rango} dias"),
                'repartidores' => $this->descargarHoja($this->reportes->hojaRepartidores($tenantId, $rango), "Repartidores {$rango} dias"),
                default => $this->excel->descargarLibro("Reporte {$rango} dias", [
                    $this->reportes->hojaVentas($tenantId, $rango),
                    $this->reportes->hojaRepartidores($tenantId, $rango),
                ]),
            };
        } catch (\Throwable $e) {
            Log::error('Error al exportar reporte', ['error' => $e->getMessage(), 'archivo' => $e->getFile() . ':' . $e->getLine()]);

            return $request->expectsJson()
                ? response()->json(['message' => 'No se pudo generar el Excel. Intenta de nuevo en unos segundos.'], 500)
                : back()->with('error', 'No se pudo generar el Excel. Intenta de nuevo en unos segundos.');
        }
    }

    private function descargarHoja(array $hoja, string $archivo): Response
    {
        return $this->excel->descargar($archivo, $hoja['columnas'], $hoja['filas'], $hoja['totales']);
    }
}
