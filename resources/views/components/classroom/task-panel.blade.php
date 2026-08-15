@props(['content', 'entrega' => null, 'puedeEntregar' => false])

{{--
    La parte operativa de una tarea: cuándo vence, cómo se entrega, y en qué
    quedó.

    Lo importante es que el alumno pueda distinguir tres situaciones que se
    parecen: no entregué, entregué y todavía no la miraron, y ya está corregida.
    En el sistema que tomamos de referencia la nota simplemente no aparecía
    hasta que el docente la publicaba, sin decir nada — y el alumno no sabía si
    se había perdido su trabajo.
--}}
<div class="mt-5 border-t border-line pt-4">
    @if ($content->due_date)
        <p @class([
            'text-xs',
            'text-subtle' => ! $content->isPastDue(),
            'text-red-700 dark:text-red-400' => $content->isPastDue(),
        ])>
            {{ $content->isPastDue() ? 'Venció el' : 'Se entrega hasta el' }}
            {{ $content->due_date->format('d/m/Y') }}
        </p>
    @endif

    @if ($entrega)
        <div class="mt-3 rounded border border-line bg-canvas p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm">
                    Entregaste el {{ $entrega->submitted_at->format('d/m/Y H:i') }}
                    @if ($entrega->attempt_number > 1)
                        <span class="text-subtle">· entrega {{ $entrega->attempt_number }}</span>
                    @endif
                </p>

                @if ($entrega->url())
                    <a href="{{ $entrega->url() }}" target="_blank" rel="noopener noreferrer"
                       class="text-sm text-brand-text">
                        Ver lo que entregué
                    </a>
                @endif
            </div>

            @if ($entrega->isVisibleToStudent())
                <div class="mt-3 border-t border-line pt-3">
                    <p class="text-sm">
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-primary-50 text-primary-800' => $entrega->status === \App\Enums\SubmissionStatus::Approved,
                            'bg-red-50 text-red-800' => $entrega->status === \App\Enums\SubmissionStatus::Rejected,
                        ])>{{ $entrega->status->getLabel() }}</span>

                        <span class="ml-2">Nota: <strong class="tabular-nums">{{ rtrim(rtrim($entrega->grade, '0'), '.') }}</strong></span>
                    </p>

                    @if ($entrega->feedback)
                        <p class="mt-2 text-sm">
                            <span class="text-subtle">Devolución:</span> {{ $entrega->feedback }}
                        </p>
                    @endif
                </div>
            @else
                {{-- Corregida sin publicar se ve igual que sin corregir, y está
                     bien: lo que no puede pasar es que el alumno no sepa nada --}}
                <p class="mt-3 border-t border-line pt-3 text-sm text-subtle">
                    En corrección. Vas a ver la nota cuando el docente la publique.
                </p>
            @endif
        </div>
    @endif

    @if ($puedeEntregar)
        <form method="POST"
              action="{{ route('classroom.submit', $content) }}"
              enctype="multipart/form-data"
              class="mt-3">
            @csrf

            <label for="archivo-{{ $content->id }}" class="field-label">
                {{ $entrega ? 'Volver a entregar' : 'Tu entrega' }}
            </label>

            <div class="flex flex-wrap items-center gap-3">
                <input type="file"
                       name="archivo"
                       id="archivo-{{ $content->id }}"
                       accept=".pdf,.doc,.docx,.odt"
                       required
                       class="field max-w-md text-sm">

                <x-button type="submit">Entregar</x-button>
            </div>

            <p class="mt-1 text-xs text-subtle">PDF o documento de texto, hasta 10 MB.</p>

            @error('archivo')
                <p class="mt-1 text-xs text-error" role="alert">{{ $message }}</p>
            @enderror
        </form>
    @elseif (! $entrega && $content->isPastDue())
        <p class="mt-3 text-sm text-subtle">La fecha de entrega venció y no llegaste a entregar.</p>
    @endif
</div>
