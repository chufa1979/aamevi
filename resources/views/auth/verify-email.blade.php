@extends('layouts.guest')

@section('title', 'Verificá tu correo')

{{--
    Va en el layout de invitado aunque haya sesión iniciada: quien está acá
    todavía no puede entrar a ningún lado, y mostrarle la navegación del aula
    sería ofrecerle puertas que le van a rebotar.
--}}
@section('content')
    <h1 class="mb-1 text-2xl font-normal">Verificá tu correo</h1>
    <p class="mb-6 text-sm text-subtle">Falta un paso para empezar.</p>

    @if (session('exito'))
        <div class="mb-5 border-l-4 border-primary bg-card p-3 text-sm" role="status">
            {{ session('exito') }}
        </div>
    @endif

    <div class="bg-card p-6 text-sm leading-relaxed">
        <p class="mb-4">
            Le mandamos un correo a <strong>{{ auth()->user()->email }}</strong> con
            un enlace para confirmar que la dirección es tuya. Abrilo y volvés
            directo a la plataforma.
        </p>

        <p class="mb-4 text-subtle">
            Si no te llegó, revisá el correo no deseado. También puede tardar unos
            minutos.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button type="submit">Reenviar el correo</x-button>
        </form>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center text-sm">
        @csrf
        <button type="submit" class="cursor-pointer underline underline-offset-2 hover:text-accent">
            Cerrar sesión
        </button>
    </form>
@endsection
