@extends('layouts.app')

@section('title', $title . ' - AAMEVi')

{{--
    Cubre las secciones que todavía no están implementadas, para que la
    navegación del layout no apunte a 404 mientras se construyen los módulos.
--}}
@section('content')
    <x-page-hero :title="$title" />

    <x-section narrow>
        <p class="text-lg font-light">Esta sección todavía está en construcción.</p>
        <div class="mt-6">
            <x-button href="/">Volver al inicio</x-button>
        </div>
    </x-section>
@endsection
