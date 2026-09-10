@extends('layouts.app')

@section('title', 'AAMEVi - Educación')

@section('content')
    <x-page-hero title="Educación" size="full" image="{{ asset('images/home-hero.jpg') }}" />

    <x-section
        title="Cursos"
        subtitle="Formación en medicina del estilo de vida para profesionales de la salud."
        narrow
    >
        @if ($cursos->isEmpty())
            <div class="card p-6">
                <p>No hay cursos abiertos en este momento.</p>
            </div>
        @else
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($cursos as $course)
                    <li>
                        <a href="{{ route('course.showcase', $course) }}"
                           class="group block h-full bg-card no-underline transition-shadow hover:shadow-lg">
                            <div class="h-2 bg-primary"></div>
                            <div class="p-6">
                                <h3 class="mb-2 text-lg font-bold leading-snug group-hover:text-accent">
                                    {{ $course->title }}
                                </h3>

                                <p class="mb-3 text-xs text-subtle">
                                    {{ $course->teacher?->user?->full_name ?? 'Sin docente asignado' }}
                                    · {{ $course->modules_count }} {{ $course->modules_count === 1 ? 'módulo' : 'módulos' }}
                                </p>

                                @if ($course->start_date)
                                    <p class="text-xs text-subtle">
                                        Inicia el {{ $course->start_date->format('d/m/Y') }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-section>

    <section>
        <h2 class="bg-ink py-2 text-center text-sm uppercase text-white">Sumate a AAMEVi</h2>
        <div class="container-site-sm py-10 text-center md:py-16">
            <p class="mx-auto max-w-xl text-xl font-light">
                Creá tu cuenta y pedí inscripción al curso que quieras cursar.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-6">
                <x-button href="{{ route('register') }}" cta>Crear cuenta</x-button>
            </div>
        </div>
    </section>
@endsection
