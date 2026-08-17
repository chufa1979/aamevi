{{--
    Detalle de un intento: qué preguntas le tocaron a este alumno y qué eligió.

    Es la razón de ser de `quiz_question_assignment`. Como el sorteo es por
    alumno, sin ese registro una nota reclamada no se podría reconstruir: no
    habría manera de saber sobre qué se lo evaluó.

    El enunciado se imprime con `strip_tags` y no con el componente de texto
    enriquecido porque acá va dentro de un modal del panel, que tiene su propia
    tipografía.

    Las clases son `.aamevi-*` de `resources/css/filament/admin.css` y no
    utilidades de Tailwind: el panel sirve su propio CSS y no carga el build del
    sitio, así que acá `flex` o `text-sm` no significan nada.
--}}
<div class="aamevi-modal">
    <div class="aamevi-attempt-meta">
        <span>
            {{ $attempt->quiz?->isModuleExam() ? 'Examen del módulo' : 'Autoevaluación' }}
        </span>
        <span>Intento {{ $attempt->attempt_number }}</span>
        <span>Entregado el {{ $attempt->submitted_at?->format('d/m/Y H:i') }}</span>
        <span>
            Nota <strong>{{ $attempt->score }}%</strong>
            sobre {{ $attempt->quiz?->passing_score }}% necesarios
        </span>
        <span class="{{ $attempt->passed ? 'is-aprobado' : 'is-desaprobado' }}">
            {{ $attempt->passed ? 'Aprobado' : 'No aprobado' }}
        </span>
    </div>

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
</div>
