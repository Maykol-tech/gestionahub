<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Datos del formulario de ingreso al panel. */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
            'recordar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Ingresa un correo válido.',
            'email.max' => 'El correo es demasiado largo.',
            'password.required' => 'Ingresa tu contraseña.',
            'password.max' => 'La contraseña es demasiado larga.',
        ];
    }

    /** Solo correo y contraseña van a Auth::attempt. */
    public function credenciales(): array
    {
        return $this->only('email', 'password');
    }
}
