@extends('layouts.classroom')

@section('title', 'Mi progreso')

@section('content')
    <h1 class="mb-6 text-2xl font-medium">Mi progreso</h1>

    @if ($cursos->isEmpty())
        <div class="card p-6">
            <p>Todavía no tenés cursos en marcha.</p>
        </div>
    @else
        <ul class="grid gap-4">
            @foreach ($cursos as $fila)
                <li class="card p-5">
                    <h2 class="text-lg font-medium leading-snug">
                        <a href="{{ route('classroom.course', $fila['course']) }}"
                           class="text-brand-text no-underline hover:underline">
                            {{ $fila['course']->title }}
                        </a>
                    </h2>

                    <div class="mt-4">
                        <x-classroom.progress-bar
                            :value="$fila['avance']"
                            :label="'Avance en '.$fila['course']->title"
                            :detail="$fila['aprobadas'].' de '.$fila['total'].' clases aprobadas'" />
                    </div>

                    <dl class="mt-4 grid grid-cols-3 gap-3 text-center text-sm">
                        <div class="rounded bg-canvas p-3">
                            <dt class="text-xs text-subtle">Aprobadas</dt>
                            <dd class="text-lg font-medium tabular-nums">{{ $fila['aprobadas'] }}</dd>
                        </div>

                        <div class="rounded bg-canvas p-3">
                            <dt class="text-xs text-subtle">Empezadas</dt>
                            <dd class="text-lg font-medium tabular-nums">{{ $fila['enCurso'] }}</dd>
                        </div>

                        <div class="rounded bg-canvas p-3">
                            <dt class="text-xs text-subtle">Sin empezar</dt>
                            <dd class="text-lg font-medium tabular-nums">{{ $fila['disponibles'] }}</dd>
                        </div>
                    </dl>

                    {{-- La respuesta a «¿y ahora qué?»: la próxima que puede abrir --}}
                    @if ($fila['siguiente'])
                        <p class="mt-4 text-sm">
                            Seguís por
                            <a href="{{ route('classroom.class', $fila['siguiente']) }}" class="text-brand-text">
                                {{ $fila['siguiente']->title }}
                            </a>
                        </p>
                    @elseif ($fila['avance'] === 100)
                        <p class="mt-4 text-sm text-subtle">Completaste todas las clases de este curso.</p>
                    @else
                        <p class="mt-4 text-sm text-subtle">
                            No hay clases disponibles ahora mismo: esperá a que se habilite la próxima.
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
@endsection
