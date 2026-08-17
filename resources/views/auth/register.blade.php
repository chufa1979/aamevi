@extends('layouts.guest')

@section('title', 'Crear cuenta')

@section('content')
    <h1 class="mb-1 text-2xl font-normal">Crear cuenta</h1>
    <p class="mb-6 text-sm text-subtle">
        Con la cuenta vas a poder ver el catálogo y solicitar inscripción a los cursos.
    </p>

    @if ($errors->any())
        <div class="mb-5 border-l-4 border-error bg-card p-3 text-sm text-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="bg-card p-6">
        @csrf

        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="field-label">Nombre</label>
                <input id="first_name" name="first_name" type="text" class="field"
                       value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
            </div>

            <div>
                <label for="last_name" class="field-label">Apellido</label>
                <input id="last_name" name="last_name" type="text" class="field"
                       value="{{ old('last_name') }}" required autocomplete="family-name">
            </div>
        </div>

        <div class="mb-4">
            <label for="email" class="field-label">Correo electrónico</label>
            <input id="email" name="email" type="email" class="field"
                   value="{{ old('email') }}" required autocomplete="email">
            <p class="mt-1 text-xs text-subtle">Te vamos a mandar un correo para verificarlo.</p>
        </div>

        <div class="mb-4">
            <label for="dni" class="field-label">DNI <span class="normal-case">(opcional)</span></label>
            <input id="dni" name="dni" type="text" class="field"
                   value="{{ old('dni') }}" inputmode="numeric" autocomplete="off">
            <p class="mt-1 text-xs text-subtle">Hace falta para emitir el certificado; lo podés completar después.</p>
        </div>

        <div class="mb-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Contraseña</label>
                <input id="password" name="password" type="password" class="field"
                       required autocomplete="new-password">
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Repetila</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="field"
                       required autocomplete="new-password">
            </div>
        </div>

        <x-button type="submit" class="w-full">Crear cuenta</x-button>
    </form>

    <p class="mt-6 text-center text-sm">
        ¿Ya tenés cuenta?
        <a href="{{ route('login') }}" class="underline underline-offset-2 hover:text-accent">Iniciar sesión</a>
    </p>
@endsection
