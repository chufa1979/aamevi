@extends('layouts.classroom')

@section('title', 'Catálogo')

@section('content')
    <h1 class="mb-2 text-2xl font-medium">Catálogo</h1>
    <p class="mb-6 text-sm text-subtle">
        Cursos abiertos a los que todavía no te anotaste. La inscripción la aprueba el docente.
    </p>

    @if ($cursos->isEmpty())
        <div class="card p-6">
            <p>No hay cursos disponibles para inscribirte en este momento.</p>
            <p class="mt-2 text-sm text-subtle">
                Puede ser que ya estés en todos, o que los abiertos hayan llegado a su cupo.
            </p>
        </div>
    @else
        <ul class="grid gap-4">
            @foreach ($cursos as $course)
                <li class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="text-lg font-medium leading-snug">{{ $course->title }}</h2>

                            <p class="mt-1 text-xs text-subtle">
                                {{ $course->teacher?->user?->full_name ?? 'Sin docente asignado' }}
                                · {{ $course->modules_count }} {{ $course->modules_count === 1 ? 'módulo' : 'módulos' }}
                                · {{ $course->max_students - $course->occupiedSeats() }} lugares disponibles
                            </p>
                        </div>

                        <form method="POST" action="{{ route('classroom.enroll', $course) }}">
                            @csrf
                            <x-button type="submit">Solicitar inscripción</x-button>
                        </form>
                    </div>

                    <x-rich-text :html="$course->description" class="mt-4 text-sm" />
                </li>
            @endforeach
        </ul>
    @endif
@endsection
