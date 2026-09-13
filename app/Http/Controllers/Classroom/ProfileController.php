<?php

namespace App\Http\Controllers\Classroom;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Classroom\UpdateProfileRequest;

/**
 * "Mi perfil" del alumno.
 *
 * Sin email ni contraseña, a propósito: son la cuenta, no la ficha, y
 * cambiarlos es un paso más sensible (verificación de la nueva casilla,
 * etc.) que no es lo que se pidió acá. Si hace falta, es un pedido aparte.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('classroom.profile', ['alumno' => auth()->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'first_name' => $request->string('first_name')->trim()->value(),
            'last_name' => $request->string('last_name')->trim()->value(),
        ]);

        $user->student->update([
            'dni' => $request->filled('dni') ? $request->string('dni')->trim()->value() : null,
            'country' => $request->filled('country') ? $request->string('country')->trim()->value() : null,
            'city' => $request->filled('city') ? $request->string('city')->trim()->value() : null,
            'phone' => $request->filled('phone') ? $request->string('phone')->trim()->value() : null,
            'date_of_birth' => $request->date('date_of_birth'),
            'graduation_date' => $request->date('graduation_date'),
            'university' => $request->filled('university') ? $request->string('university')->trim()->value() : null,
            'profession' => $request->filled('profession') ? $request->string('profession')->trim()->value() : null,
            'membership_number' => $request->filled('membership_number')
                ? $request->string('membership_number')->trim()->value()
                : null,
        ]);

        return back()->with('exito', 'Guardamos tus datos.');
    }
}
