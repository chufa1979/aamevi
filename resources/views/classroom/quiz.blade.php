@extends('layouts.classroom')

@section('title', $quiz->title ?? 'Evaluación')

@section('content')
    <h1 class="text-2xl font-medium leading-snug">
        {{ $quiz->isModuleExam() ? 'Examen del módulo' : 'Autoevaluación' }}
    </h1>

    <p class="mt-1 text-sm text-subtle">
        {{ $quiz->isModuleExam() ? $quiz->module?->title : $quiz->class?->title }}
    </p>

    <div class="card mt-6 p-5">
        <p class="text-sm">
            Son {{ $attempt->questions->count() }}
            {{ $attempt->questions->count() === 1 ? 'pregunta' : 'preguntas' }} de opción múltiple.
            Se aprueba con {{ $quiz->passing_score }}%.
            Te {{ $intentosRestantes === 1 ? 'queda' : 'quedan' }} {{ $intentosRestantes }}
            {{ $intentosRestantes === 1 ? 'intento' : 'intentos' }}.
        </p>
    </div>

    {{--
        Las opciones llevan sólo su texto y su id. Cuál es la correcta no sale
        del servidor: lo resuelve `QuizService::submit()` contra la base.
    --}}
    <form method="POST" action="{{ route('classroom.quiz.submit', $quiz) }}" class="mt-6 space-y-5">
        @csrf

        @foreach ($attempt->questions as $i => $question)
            <fieldset class="card p-5">
                <legend class="text-xs uppercase tracking-wide text-subtle">
                    Pregunta {{ $i + 1 }} de {{ $attempt->questions->count() }}
                </legend>

                <x-rich-text :html="$question->text" class="mt-2 font-medium" />

                <div class="mt-4 space-y-2">
                    @foreach ($quiz->randomize_options ? $question->options->shuffle() : $question->options as $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded border border-line p-3 text-sm transition-colors hover:bg-canvas">
                            <input type="radio"
                                   name="respuestas[{{ $question->id }}]"
                                   value="{{ $option->id }}"
                                   class="mt-0.5 shrink-0">
                            <span>{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit">Entregar</x-button>

            <p class="text-xs text-subtle">Las preguntas sin responder cuentan como incorrectas.</p>
        </div>
    </form>
@endsection
