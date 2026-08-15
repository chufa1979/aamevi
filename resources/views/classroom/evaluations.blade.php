@extends('layouts.classroom')

@section('title', 'Mis evaluaciones')

@section('content')
    <h1 class="text-2xl font-medium leading-snug">Mis evaluaciones</h1>
    <p class="mt-1 text-sm text-subtle">{{ $course->title }}</p>

    @if ($evaluaciones->isEmpty())
        <div class="card mt-6 p-5">
            <p class="text-sm text-subtle">Este curso todavía no tiene evaluaciones cargadas.</p>
        </div>
    @else
        <ul class="card mt-6 divide-y divide-line">
            @foreach ($evaluaciones as $fila)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium">
                            {{ $fila['titulo'] }}
                            @if ($fila['esExamen'])
                                <span class="ml-1 rounded bg-canvas px-1.5 py-0.5 text-[11px] uppercase tracking-wide text-subtle">
                                    Examen
                                </span>
                            @endif
                        </p>

                        <p class="mt-0.5 text-xs text-subtle">
                            {{ $fila['modulo'] }}
                            · {{ $fila['intentos'] }} {{ $fila['intentos'] === 1 ? 'intento' : 'intentos' }}
                            @if ($fila['restantes'] > 0)
                                · {{ $fila['restantes'] }} {{ $fila['restantes'] === 1 ? 'disponible' : 'disponibles' }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm tabular-nums">
                            {{ $fila['mejorNota'] !== null ? $fila['mejorNota'].'%' : '—' }}
                        </span>

                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-primary-50 text-primary-800' => $fila['aprobado'],
                            'bg-canvas text-subtle' => ! $fila['aprobado'] && $fila['intentos'] === 0,
                            'bg-red-50 text-red-800' => ! $fila['aprobado'] && $fila['intentos'] > 0,
                        ])>
                            @if ($fila['aprobado'])
                                Aprobada
                            @elseif ($fila['intentos'] === 0)
                                Sin rendir
                            @else
                                No aprobada
                            @endif
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
