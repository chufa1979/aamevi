@extends('layouts.classroom')

@section('title', 'Consultas')

@section('content')
    <h1 class="mb-2 text-2xl font-medium">Consultas</h1>
    <p class="mb-6 text-sm text-subtle">
        Preguntas sobre un curso. Las responde quien lo dicta.
    </p>

    @if ($cursos->isEmpty())
        <div class="card p-6">
            <p>Para consultar algo tenés que estar cursando.</p>
            <p class="mt-4">
                <x-button href="{{ route('classroom.catalog') }}">Ver el catálogo</x-button>
            </p>
        </div>
    @else
        <details class="card mb-6 p-5" @if ($errors->any() || old('subject')) open @endif>
            <summary class="cursor-pointer font-medium">Hacer una consulta</summary>

            <form method="POST" action="{{ route('classroom.tickets.store') }}" class="mt-4">
                @csrf

                <div class="mb-4">
                    <label for="course_id" class="field-label">Curso</label>
                    <select id="course_id" name="course_id" class="field" required>
                        @foreach ($cursos as $curso)
                            <option value="{{ $curso->getKey() }}" @selected(old('course_id') === $curso->getKey())>
                                {{ $curso->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="subject" class="field-label">Asunto</label>
                    <input id="subject" name="subject" type="text" class="field"
                           value="{{ old('subject') }}" maxlength="150" required>
                </div>

                <div class="mb-4">
                    <label for="message" class="field-label">Consulta</label>
                    <textarea id="message" name="message" rows="5" class="field" required>{{ old('message') }}</textarea>
                </div>

                <x-button type="submit">Enviar</x-button>
            </form>
        </details>

        @if ($consultas->isEmpty())
            <div class="card p-6">
                <p>Todavía no hiciste ninguna consulta.</p>
            </div>
        @else
            <ul class="grid gap-3">
                @foreach ($consultas as $consulta)
                    <li class="card flex flex-wrap items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <a href="{{ route('classroom.ticket', $consulta) }}"
                               class="font-medium text-brand-text no-underline hover:underline">
                                {{ $consulta->subject }}
                            </a>
                            <p class="mt-1 text-xs text-subtle">
                                {{ $consulta->course?->title }} ·
                                {{ $consulta->updated_at->format('d/m/Y') }} ·
                                {{ $consulta->messages_count }}
                                {{ $consulta->messages_count === 1 ? 'mensaje' : 'mensajes' }}
                            </p>
                        </div>

                        <x-classroom.badge :label="$consulta->status->getLabel()"
                                           :color="$consulta->status->getColor()"
                                           :icon="match ($consulta->status) {
                                               \App\Enums\TicketStatus::Open => 'agendada',
                                               \App\Enums\TicketStatus::Answered => 'texto',
                                               \App\Enums\TicketStatus::Closed => 'aprobada',
                                           }" />
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
@endsection
