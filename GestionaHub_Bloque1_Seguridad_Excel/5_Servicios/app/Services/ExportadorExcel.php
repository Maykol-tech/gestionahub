<?php

namespace App\Services;

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
     * @param  array<int, array{titulo: string, tipo?: string}>  $columnas
     * @param  array<int, array<int, mixed>>  $filas
     */
    public function descargar(string $titulo, array $columnas, array $filas, bool $conTotales = true): BinaryFileResponse
    {
        $usuario = auth()->user();
        $negocio = $usuario?->tenant_id ? Tenant::query()->whereKey($usuario->tenant_id)->value('nombre_negocio') : null;

        $archivo = Str::slug($titulo) . ($negocio ? '-' . Str::slug($negocio) : '') . '-' . now('America/Lima')->format('Ymd-His') . '.xlsx';

        Log::info('Exportacion Excel', [
            'usuario' => $usuario?->id, 'tenant' => $usuario?->tenant_id, 'titulo' => $titulo, 'filas' => count($filas),
        ]);

        try {
            return Excel::download(new TablaExport($titulo, $columnas, $filas, $conTotales, $negocio), $archivo);
        } finally {
            // Laravel-Excel deja el "value binder" de la exportacion como global: se restablece
            // para que otras lecturas o escrituras de hojas en el mismo proceso no se vean afectadas.
            Cell::setValueBinder(new DefaultValueBinder);
        }
    }
}
