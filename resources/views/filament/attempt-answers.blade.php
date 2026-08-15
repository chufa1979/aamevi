{{--
    Detalle de un intento: qué preguntas le tocaron a este alumno y qué eligió.

    Es la razón de ser de `quiz_question_assignment`. Como el sorteo es por
    alumno, sin ese registro una nota reclamada no se podría reconstruir: no
    habría manera de saber sobre qué se lo evaluó.

    El enunciado se imprime con `strip_tags` y no con el componente de texto
    enriquecido porque acá va dentro de un modal del panel, que tiene su propia
    tipografía.
--}}
<div class="space-y-4 text-sm">
    <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
        <span>
            {{ $attempt->quiz?->isModuleExam() ? 'Examen del módulo' : 'Autoevaluación' }}
        </span>
        <span>Intento {{ $attempt->attempt_number }}</span>
        <span>Entregado el {{ $attempt->submitted_at?->format('d/m/Y H:i') }}</span>
        <span>
            Nota <strong class="text-gray-950 dark:text-white">{{ $attempt->score }}%</strong>
            sobre {{ $attempt->quiz?->passing_score }}% necesarios
        </span>
        <span @class([
            'font-medium',
            'text-success-600 dark:text-success-400' => $attempt->passed,
            'text-danger-600 dark:text-danger-400' => ! $attempt->passed,
        ])>
            {{ $attempt->passed ? 'Aprobado' : 'No aprobado' }}
        </span>
    </div>

    <ol class="space-y-3">
        @foreach ($attempt->answers as $answer)
            <li class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                <div class="flex items-start gap-2">
                    <x-filament::icon
                        :icon="$answer->is_correct ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                        @class([
                            'mt-0.5 h-4 w-4 shrink-0',
                            'text-success-600 dark:text-success-400' => $answer->is_correct,
                            'text-danger-600 dark:text-danger-400' => ! $answer->is_correct,
                        ])
                    />

                    <p class="font-medium text-gray-950 dark:text-white">
                        {{ strip_tags((string) $answer->question?->text) }}
                    </p>
                </div>

                <p class="mt-2 pl-6 text-gray-600 dark:text-gray-400">
                    Respondió:
                    <span class="text-gray-950 dark:text-white">
                        {{ $answer->selectedOption?->option_text ?? '— sin responder —' }}
                    </span>
                </p>

                @unless ($answer->is_correct)
                    <p class="mt-1 pl-6 text-gray-600 dark:text-gray-400">
                        Correcta:
                        <span class="text-success-700 dark:text-success-400">
                            {{ $answer->question?->correctOption()?->option_text }}
                        </span>
                    </p>
                @endunless
            </li>
        @endforeach
    </ol>
</div>
