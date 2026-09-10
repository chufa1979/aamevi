<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de cuenta desde el sitio.
 *
 * Sólo el nombre, el correo y la contraseña son obligatorios: el resto de la
 * ficha —documento, residencia, egreso, profesión, N° de socio— se puede
 * completar acá o más adelante desde la administración. Nada de esto abre el
 * aula por sí solo: un curso sigue necesitando inscripción y aprobación, así
 * que el formulario puede quedar abierto sin ese riesgo.
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
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'graduation_date' => ['nullable', 'date', 'before_or_equal:today'],
            'university' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'membership_number' => ['nullable', 'string', 'max:50'],
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
            'date_of_birth.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'graduation_date.before_or_equal' => 'La fecha de egreso no puede ser futura.',
        ];
    }
}
