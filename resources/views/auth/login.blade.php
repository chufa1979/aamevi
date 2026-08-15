@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <h1 class="mb-1 text-2xl font-normal">Iniciar sesión</h1>
    <p class="mb-6 text-sm text-subtle">Ingresá con tu cuenta para acceder a la plataforma.</p>

    @if ($errors->any())
        <div class="mb-5 border-l-4 border-error bg-card p-3 text-sm text-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('status'))
        <div class="mb-5 border-l-4 border-primary bg-card p-3 text-sm" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="bg-card p-6">
        @csrf

        <div class="mb-4">
            <label for="email" class="field-label">Correo electrónico</label>
            <input id="email" name="email" type="email" class="field"
                   value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="mb-4">
            <label for="password" class="field-label">Contraseña</label>
            <input id="password" name="password" type="password" class="field"
                   required autocomplete="current-password">
        </div>

        <label class="mb-6 flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" class="accent-primary"
                   @checked(old('remember'))>
            Mantener la sesión iniciada
        </label>

        <x-button type="submit" class="w-full">Ingresar</x-button>
    </form>

    <p class="mt-6 text-center text-sm">
        ¿Todavía no tenés cuenta?
        <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-accent">Crear una cuenta</a>
    </p>
@endsection
