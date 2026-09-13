@extends('layouts.classroom')

@section('title', 'Mi perfil')

{{--
    Mismos campos que el registro (auth/register.blade.php), menos cuenta
    —email y contraseña—: eso es un paso más sensible que no es lo que se
    pidió acá.
--}}
@section('content')
    <h1 class="mb-6 text-2xl font-medium">Mi perfil</h1>

    @if ($errors->any())
        <div class="mb-5 border-l-4 border-error bg-card p-3 text-sm text-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('classroom.profile.update') }}" class="bg-card p-6">
        @csrf
        @method('PUT')

        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="field-label">Nombre</label>
                <input id="first_name" name="first_name" type="text" class="field"
                       value="{{ old('first_name', $alumno->first_name) }}" required>
            </div>

            <div>
                <label for="last_name" class="field-label">Apellido</label>
                <input id="last_name" name="last_name" type="text" class="field"
                       value="{{ old('last_name', $alumno->last_name) }}" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="dni" class="field-label">N° Documento <span class="normal-case">(opcional)</span></label>
            <input id="dni" name="dni" type="text" class="field"
                   value="{{ old('dni', $alumno->student->dni) }}" inputmode="numeric" autocomplete="off">
            <p class="mt-1 text-xs text-subtle">Hace falta para emitir el certificado.</p>
        </div>

        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="country" class="field-label">País de residencia</label>
                <input id="country" name="country" type="text" class="field"
                       value="{{ old('country', $alumno->student->country) }}" autocomplete="country-name">
            </div>

            <div>
                <label for="city" class="field-label">Ciudad</label>
                <input id="city" name="city" type="text" class="field"
                       value="{{ old('city', $alumno->student->city) }}" placeholder="Ciudad / Estado" autocomplete="address-level2">
            </div>
        </div>

        <div class="mb-4">
            <label for="phone" class="field-label">Teléfono</label>
            <input id="phone" name="phone" type="tel" class="field"
                   value="{{ old('phone', $alumno->student->phone) }}" placeholder="+54 9 ..." autocomplete="tel">
        </div>

        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="date_of_birth" class="field-label">Fecha de nacimiento</label>
                <input id="date_of_birth" name="date_of_birth" type="date" class="field"
                       value="{{ old('date_of_birth', $alumno->student->date_of_birth?->format('Y-m-d')) }}"
                       max="{{ now()->toDateString() }}">
            </div>

            <div>
                <label for="graduation_date" class="field-label">Fecha de egreso</label>
                <input id="graduation_date" name="graduation_date" type="date" class="field"
                       value="{{ old('graduation_date', $alumno->student->graduation_date?->format('Y-m-d')) }}"
                       max="{{ now()->toDateString() }}">
                <p class="mt-1 text-xs text-subtle">Si solo tenés el año, elegí cualquier día de ese año.</p>
            </div>
        </div>

        <div class="mb-4">
            <label for="university" class="field-label">Universidad / Instituto</label>
            <input id="university" name="university" type="text" class="field"
                   value="{{ old('university', $alumno->student->university) }}" autocomplete="organization">
        </div>

        <div class="mb-4">
            <label for="profession" class="field-label">Profesión</label>
            <input id="profession" name="profession" type="text" class="field"
                   value="{{ old('profession', $alumno->student->profession) }}" placeholder="Profesional sin matrícula">
        </div>

        <div class="mb-6">
            <label for="membership_number" class="field-label">N° de socio <span class="normal-case">(opcional)</span></label>
            <input id="membership_number" name="membership_number" type="text" class="field"
                   value="{{ old('membership_number', $alumno->student->membership_number) }}" autocomplete="off">
            <p class="mt-1 text-xs text-subtle">Si tenés, ingresalo. Si no, seguís como no socio.</p>
        </div>

        <x-button type="submit">Guardar cambios</x-button>
    </form>
@endsection
