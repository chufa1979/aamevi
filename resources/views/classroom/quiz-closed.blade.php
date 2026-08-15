@extends('layouts.classroom')

@section('title', 'Evaluación')

@section('content')
    <h1 class="text-2xl font-medium leading-snug">
        {{ $quiz->isModuleExam() ? 'Examen del módulo' : 'Autoevaluación' }}
    </h1>

    <div class="card mt-6 p-5">
        <p class="text-sm">{{ $motivo }}</p>

        @if ($aprobado)
            <p class="mt-2 text-sm">Ya la aprobaste, así que no necesitás rendirla de nuevo.</p>
        @endif
    </div>

    @if ($intentos->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-medium">Tus intentos</h2>

        <ul class="card divide-y divide-line">
            @foreach ($intentos as $intento)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                    <span>
                        Intento {{ $intento->attempt_number }}
                        <span class="text-subtle">
                            · {{ $intento->submitted_at?->format('d/m/Y H:i') ?? 'sin entregar' }}
                        </span>
                    </span>

                    <span class="flex items-center gap-3">
                        <span class="tabular-nums">{{ $intento->score !== null ? $intento->score.'%' : '—' }}</span>

                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-primary-50 text-primary-800' => $intento->passed,
                            'bg-red-50 text-red-800' => ! $intento->passed,
                        ])>
                            {{ $intento->passed ? 'Aprobado' : 'No aprobado' }}
                        </span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-6">
        @if ($quiz->class)
            <x-button href="{{ route('classroom.class', $quiz->class) }}" variant="ghost">Volver a la clase</x-button>
        @elseif ($cursoActual)
            <x-button href="{{ route('classroom.course', $cursoActual) }}" variant="ghost">Volver al temario</x-button>
        @endif
    </div>
@endsection
