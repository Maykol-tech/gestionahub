<?php

namespace App\Http\Requests;

use App\Services\ReportesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Exportacion de reportes (GEST-40 y GEST-41).
 *  - rango: 7, 30 o 90 dias.
 *  - hoja:  todo (libro con ambas hojas), ventas (GEST-40) o repartidores (GEST-41).
 */
class ReporteExportarRequest extends FormRequest
{
    public const HOJAS = ['todo', 'ventas', 'repartidores'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rango' => $this->query('rango', 7),
            'hoja' => $this->query('hoja', 'todo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'rango' => ['required', 'integer', Rule::in(ReportesService::RANGOS)],
            'hoja' => ['required', 'string', Rule::in(self::HOJAS)],
        ];
    }

    public function messages(): array
    {
        return [
            'rango.in' => 'El periodo debe ser de 7, 30 o 90 días.',
            'hoja.in' => 'El reporte solicitado no existe.',
        ];
    }

    public function rango(): int
    {
        return (int) $this->validated('rango');
    }

    public function hoja(): string
    {
        return (string) $this->validated('hoja');
    }
}
