@extends('layouts.guest')

@section('title', 'Crear cuenta')

{{--
    Marcador de posición: el registro se implementa en la Fase 1 junto con la
    verificación por email (docs/PLAN_ARQUITECTONICO.md §3-A). Vive en el layout
    de invitado para no exponer la navegación de la plataforma.
--}}
@section('content')
    <h1 class="mb-1 text-2xl font-normal">Crear cuenta</h1>
    <p class="mb-6 text-sm text-ink/70">El registro todavía no está habilitado.</p>

    <div class="bg-white p-6 text-sm leading-relaxed">
        <p>
            Por el momento las cuentas las da de alta la administración de AAMEVi.
            Escribinos a
            <a href="mailto:info@aamevi.ar" class="underline underline-offset-2 hover:text-accent">info@aamevi.ar</a>
            y te la creamos.
        </p>
    </div>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="underline underline-offset-2 hover:text-accent">Volver al inicio de sesión</a>
    </p>
@endsection
