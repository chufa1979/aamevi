<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de cuenta desde el sitio.
 *
 * Pide lo mínimo para tener una cuenta usable: quién es, cómo entra, y el DNI
 * —opcional— porque es lo que va a hacer falta el día que se emita un
 * certificado. El resto de la ficha la completa la administración o el propio
 * alumno más adelante.
 *
 * Registrarse no da acceso a nada: un curso sigue necesitando inscripción y
 * aprobación. Por eso el formulario puede ser abierto sin que eso abra el aula.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'dni' => ['nullable', 'string', 'max:20', 'unique:students,dni'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ingresá tu nombre.',
            'last_name.required' => 'Ingresá tu apellido.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ese correo electrónico no es válido.',
            'email.unique' => 'Ya hay una cuenta con ese correo. Probá iniciar sesión.',
            'password.required' => 'Elegí una contraseña.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'dni.unique' => 'Ya hay una cuenta con ese DNI.',
        ];
    }
}
