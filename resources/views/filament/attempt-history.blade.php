{{--
    El historial de un alumno en una evaluación: todos sus intentos, con qué
    preguntas le tocaron y qué respondió en cada uno.

    Es la razón de ser de `quiz_question_assignment`. Como el sorteo es por
    alumno y por intento, sin ese registro una nota reclamada no se podría
    reconstruir: no habría manera de saber sobre qué se lo evaluó.

    Se muestran todos y no sólo el último porque lo que se discute suele ser
    justamente el recorrido: dos intentos con las mismas preguntas mal, o uno
    con una pregunta que estaba mal cargada.

    Las clases son `.aamevi-*` de `resources/css/filament/admin.css`: adentro de
    Filament no llegan las utilidades de Tailwind del sitio.
--}}
<div class="aamevi-modal">
    <div class="aamevi-attempt-meta">
        <span>
            {{ $quiz?->isModuleExam() ? 'Examen del módulo' : 'Autoevaluación' }}
        </span>
        <span>{{ $intentos->count() }} {{ $intentos->count() === 1 ? 'intento' : 'intentos' }}</span>
        <span>Nota mínima {{ $quiz?->passing_score }}%</span>
    </div>

    @foreach ($intentos as $attempt)
        <div class="aamevi-attempt">
            <div class="aamevi-attempt-head">
                <strong>Intento {{ $attempt->attempt_number }}</strong>

                <span>
                    @if ($attempt->isSubmitted())
                        {{ $attempt->submitted_at->format('d/m/Y H:i') }} ·
                        {{ $attempt->score }}%
                    @else
                        En curso
                    @endif
                </span>

                @if ($attempt->isSubmitted())
                    <span class="{{ $attempt->passed ? 'is-aprobado' : 'is-desaprobado' }}">
                        {{ $attempt->passed ? 'Aprobado' : 'No aprobado' }}
                    </span>
                @endif
            </div>

            @if ($attempt->answers->isEmpty())
                <p class="aamevi-thread-meta">Todavía no lo entregó.</p>
            @else
                <ol class="aamevi-answers">
                    @foreach ($attempt->answers as $answer)
                        <li class="aamevi-answer" data-correcta="{{ $answer->is_correct ? '1' : '0' }}">
                            <div class="aamevi-answer-head">
                                <x-filament::icon
                                    :icon="$answer->is_correct ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                                    class="aamevi-answer-icon"
                                />

                                <p>{{ strip_tags((string) $answer->question?->text) }}</p>
                            </div>

                            <p class="aamevi-answer-line">
                                Respondió:
                                <strong>{{ $answer->selectedOption?->option_text ?? '— sin responder —' }}</strong>
                            </p>

                            @unless ($answer->is_correct)
                                <p class="aamevi-answer-line is-correcta">
                                    Correcta:
                                    <strong>{{ $answer->question?->correctOption()?->option_text }}</strong>
                                </p>
                            @endunless
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endforeach
</div>
