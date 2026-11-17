<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta de un repartidor desde la pestaña Repartidores de Reportes (solo administradores). */
class RepartidorReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'telefono' => $this->filled('telefono') ? trim((string) $this->input('telefono')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s()-]+$/'],
            'vehiculo' => ['required', Rule::in(['moto', 'bicicleta', 'auto', 'pie'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del repartidor.',
            'nombre.min' => 'El nombre es muy corto.',
            'telefono.regex' => 'El teléfono solo puede tener números, espacios, + ( ) o -.',
            'vehiculo.in' => 'Elige un vehículo válido.',
        ];
    }
}
