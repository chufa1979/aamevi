@extends('layouts.classroom')

@section('title', 'Mis cursos')

@section('content')
    <h1 class="mb-6 text-2xl font-medium">Mis cursos</h1>

    @if ($cursando->isEmpty() && $sinResolver->isEmpty())
        <div class="card p-6">
            <p class="mb-4">Todavía no estás cursando nada.</p>
            <x-button href="{{ route('classroom.catalog') }}">Ver el catálogo</x-button>
        </div>
    @endif

    @if ($cursando->isNotEmpty())
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($cursando as $fila)
                <li class="card flex flex-col p-5">
                    <h2 class="text-lg font-medium leading-snug">
                        <a href="{{ route('classroom.course', $fila['course']) }}"
                           class="text-brand-text no-underline hover:underline">
                            {{ $fila['course']->title }}
                        </a>
                    </h2>

                    <p class="mt-1 text-xs text-subtle">
                        {{ $fila['course']->teacher?->user?->full_name ?? 'Sin docente asignado' }}
                    </p>

                    <div class="mt-4">
                        <x-classroom.progress-bar :value="$fila['avance']"
                                                  :label="'Avance en '.$fila['course']->title" />
                    </div>

                    <p class="mt-4 text-sm">
                        <a href="{{ route('classroom.course', $fila['course']) }}" class="text-brand-text">
                            {{ $fila['avance'] === 0 ? 'Empezar' : 'Continuar' }} →
                        </a>
                    </p>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($sinResolver->isNotEmpty())
        {{-- El alumno pidió algo y necesita saber en qué quedó, aunque no pueda
             entrar todavía --}}
        <h2 class="mb-3 mt-10 text-lg font-medium">Solicitudes</h2>

        <ul class="card divide-y divide-line">
            @foreach ($sinResolver as $enrollment)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <span>{{ $enrollment->course?->title }}</span>

                    <span @class([
                        'rounded-full px-2.5 py-1 text-xs font-medium',
                        'bg-accent-50 text-accent-800' => $enrollment->isPending(),
                        'bg-red-50 text-red-800' => ! $enrollment->isPending(),
                    ])>
                        {{ $enrollment->isPending() ? 'Pendiente de aprobación' : 'Rechazada' }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
