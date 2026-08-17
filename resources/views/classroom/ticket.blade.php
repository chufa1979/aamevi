@extends('layouts.classroom')

@section('title', $ticket->subject)

@section('content')
    <p class="mb-4 text-sm">
        <a href="{{ route('classroom.tickets') }}" class="inline-flex items-center gap-1 text-brand-text">
            <x-ui.icon name="volver" class="h-3.5 w-3.5" /> Todas mis consultas
        </a>
    </p>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-2xl font-medium leading-snug">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-sm text-subtle">{{ $ticket->course?->title }}</p>
        </div>

        <x-classroom.badge :label="$ticket->status->getLabel()"
                           :color="$ticket->status->getColor()"
                           :icon="match ($ticket->status) {
                               \App\Enums\TicketStatus::Open => 'agendada',
                               \App\Enums\TicketStatus::Answered => 'texto',
                               \App\Enums\TicketStatus::Closed => 'aprobada',
                           }" />
    </div>

    <ul class="mb-6 grid gap-3">
        @foreach ($ticket->messages as $mensaje)
            @php($mio = $mensaje->author_id === auth()->id())

            {{-- Los del alumno se corren a la derecha: en un hilo corto es la
                 forma más rápida de ver quién dijo qué --}}
            <li @class(['card p-4', 'ml-0 mr-auto w-full sm:w-11/12' => ! $mio, 'ml-auto mr-0 w-full border-l-4 border-l-primary sm:w-11/12' => $mio])>
                <p class="text-xs text-subtle">
                    {{ $mensaje->author?->full_name ?? 'Cuenta eliminada' }} ·
                    {{ $mensaje->created_at->format('d/m/Y H:i') }}
                </p>

                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $mensaje->body }}</p>
            </li>
        @endforeach
    </ul>

    @if ($ticket->isClosed())
        <div class="card p-5 text-sm">
            <p>Esta consulta está cerrada.</p>
            <p class="mt-2 text-subtle">Si te quedó algo pendiente, abrí una nueva desde el listado.</p>
        </div>
    @else
        <form method="POST" action="{{ route('classroom.ticket.reply', $ticket) }}" class="card p-5">
            @csrf

            <label for="message" class="field-label">Responder</label>
            <textarea id="message" name="message" rows="4" class="field" required></textarea>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-button type="submit">Enviar</x-button>
            </div>
        </form>

        {{-- Cerrar es del alumno también: si ya se lo resolvieron, no tiene por
             qué esperar a que alguien más la dé por terminada --}}
        <form method="POST" action="{{ route('classroom.ticket.close', $ticket) }}" class="mt-4 text-sm">
            @csrf
            <button type="submit" class="cursor-pointer text-subtle underline underline-offset-2 hover:text-accent-text">
                Ya no la necesito, cerrar la consulta
            </button>
        </form>
    @endif
@endsection
