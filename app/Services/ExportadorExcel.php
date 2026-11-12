<?php

namespace App\Services;

use App\Exports\LibroExport;
use App\Exports\TablaExport;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Punto unico para generar descargas .xlsx con el estilo de GestionaHub.
 * Arma el nombre del archivo (modulo + negocio + fecha) y registra cada exportacion en el log.
 */
class ExportadorExcel
{
    /**
     * Una sola hoja.
     *
     * @param  array<int, array{titulo: string, tipo?: string, suma?: bool}>  $columnas
     * @param  array<int, array<int, mixed>>  $filas
     */
    public function descargar(string $titulo, array $columnas, array $filas, bool $conTotales = true): BinaryFileResponse
    {
        $negocio = $this->negocio();

        return $this->generar(new TablaExport($titulo, $columnas, $filas, $conTotales, $negocio), $titulo, $negocio, count($filas));
    }

    /**
     * Varias hojas en un mismo archivo.
     *
     * @param  array<int, array{titulo: string, columnas: array, filas: array, totales?: bool}>  $hojas
     */
    public function descargarLibro(string $titulo, array $hojas): BinaryFileResponse
    {
        $negocio = $this->negocio();
        $tablas = array_map(
            fn ($h) => new TablaExport($h['titulo'], $h['columnas'], $h['filas'], (bool) ($h['totales'] ?? true), $negocio),
            $hojas
        );

        return $this->generar(new LibroExport($titulo, $tablas, $negocio), $titulo, $negocio, array_sum(array_map(fn ($h) => count($h['filas']), $hojas)));
    }

    private function generar(object $exportacion, string $titulo, ?string $negocio, int $filas): BinaryFileResponse
    {
        $usuario = auth()->user();
        $archivo = Str::slug($titulo) . ($negocio ? '-' . Str::slug($negocio) : '') . '-' . now('America/Lima')->format('Ymd-His') . '.xlsx';

        Log::info('Exportacion Excel', [
            'usuario' => $usuario?->id, 'tenant' => $usuario?->tenant_id, 'titulo' => $titulo, 'filas' => $filas,
        ]);

        try {
            return Excel::download($exportacion, $archivo);
        } finally {
            // Laravel-Excel deja el "value binder" de la exportacion como global: se restablece
            // para que otras lecturas o escrituras de hojas en el mismo proceso no se vean afectadas.
            Cell::setValueBinder(new DefaultValueBinder);
        }
    }

    private function negocio(): ?string
    {
        $tenantId = auth()->user()?->tenant_id;

        return $tenantId ? Tenant::query()->whereKey($tenantId)->value('nombre_negocio') : null;
    }
}
