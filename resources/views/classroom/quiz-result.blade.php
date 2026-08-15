@extends('layouts.classroom')

@section('title', 'Resultado')

@section('content')
    <h1 class="text-2xl font-medium leading-snug">
        {{ $attempt->passed ? '¡Aprobaste!' : 'No alcanzó esta vez' }}
    </h1>

    <p class="mt-1 text-sm text-subtle">
        {{ $quiz->isModuleExam() ? 'Examen del módulo' : 'Autoevaluación' }}
        · {{ $quiz->isModuleExam() ? $quiz->module?->title : $quiz->class?->title }}
    </p>

    <div class="card mt-6 p-5">
        <p class="text-sm">
            Obtuviste <strong class="tabular-nums">{{ $attempt->score }}%</strong>.
            La nota mínima es {{ $quiz->passing_score }}%.
        </p>

        @if (! $attempt->passed && $intentosRestantes > 0)
            <p class="mt-2 text-sm">
                Te {{ $intentosRestantes === 1 ? 'queda' : 'quedan' }} {{ $intentosRestantes }}
                {{ $intentosRestantes === 1 ? 'intento' : 'intentos' }}.
            </p>
        @elseif (! $attempt->passed)
            <p class="mt-2 text-sm">
                Usaste todos los intentos. Hablá con tu docente para continuar.
            </p>
        @endif
    </div>

    @if ($quiz->show_correct_answers)
        {{--
            El detalle aparece recién ahora, después de entregar. En el aula que
            tomamos de referencia la respuesta correcta viajaba al navegador
            dentro de un campo oculto y se veía con «ver código fuente»; acá la
            corrección la hizo el servidor y esto es sólo la devolución.
        --}}
        <h2 class="mb-3 mt-8 text-lg font-medium">Cómo te fue en cada pregunta</h2>

        <ol class="space-y-3">
            @foreach ($attempt->answers as $answer)
                <li class="card p-4">
                    <div class="flex items-start gap-2">
                        <x-ui.icon :name="$answer->is_correct ? 'aprobada' : 'incorrecta'"
                                   @class([
                                       'mt-1 h-4 w-4 shrink-0',
                                       'text-primary-800' => $answer->is_correct,
                                       'text-red-700' => ! $answer->is_correct,
                                   ]) />

                        <div class="min-w-0">
                            <x-rich-text :html="$answer->question?->text" class="text-sm font-medium" />

                            <p class="mt-2 text-sm">
                                Respondiste:
                                <span class="text-subtle">{{ $answer->selectedOption?->option_text ?? 'nada' }}</span>
                            </p>

                            @if (! $answer->is_correct)
                                <p class="mt-1 text-sm">
                                    Correcta:
                                    <span class="text-primary-800">{{ $answer->question?->correctOption()?->option_text }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="mt-8 flex flex-wrap gap-3">
        @if ($quiz->class)
            <x-button href="{{ route('classroom.class', $quiz->class) }}" variant="ghost">Volver a la clase</x-button>
        @elseif ($cursoActual)
            <x-button href="{{ route('classroom.course', $cursoActual) }}" variant="ghost">Volver al temario</x-button>
        @endif

        @if (! $attempt->passed && $intentosRestantes > 0)
            <x-button href="{{ route('classroom.quiz', $quiz) }}">Volver a intentar</x-button>
        @endif
    </div>
@endsection
