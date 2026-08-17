<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Student;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\Auth\RegisterRequest;

/**
 * Alta de cuenta desde el sitio.
 *
 * Crea siempre un alumno: los docentes y administradores los da de alta la
 * administración desde el panel, y un formulario público que pudiera elegir rol
 * sería una puerta abierta.
 *
 * El usuario queda con sesión iniciada pero **sin verificar**, así que lo único
 * que puede hacer es verificar su correo. Es a propósito: verificar antes de
 * dejar entrar evita que alguien se anote con la dirección de otro.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        /*
         * En transacción porque son dos filas que sólo sirven juntas: un usuario
         * con rol Alumno sin ficha no puede entrar al aula, y `EnsureStudent` lo
         * rebotaría con un mensaje que no explica nada.
         */
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'first_name' => $request->string('first_name')->trim()->value(),
                'last_name' => $request->string('last_name')->trim()->value(),
                'email' => $request->string('email')->lower()->trim()->value(),
                'password' => $request->string('password')->value(),
                'role' => UserRole::Student,
                'is_active' => true,
            ]);

            Student::create([
                'id' => $user->getKey(),
                'dni' => $request->filled('dni') ? $request->string('dni')->trim()->value() : null,
            ]);

            return $user;
        });

        // Dispara el aviso de verificación, que va a parar a `email_queue`
        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
